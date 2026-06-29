<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'ID login atau email wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('email', $request->login)
            ->orWhere('login_id', $request->login)
            ->first();

        if (!$user || !$user->is_active || !Hash::check($request->password, $user->password)) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors([
                    'login' => 'Login gagal. Periksa kembali ID login/email dan password.',
                ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectByRole($request, $user);
    }



    public function showForgotPasswordForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetCode(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar pada sistem.',
        ]);

        $email = strtolower($validated['email']);
        $user = User::where('email', $email)->first();

        if (!$user || !$user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun pengguna tidak aktif atau tidak ditemukan.']);
        }

        $otp = (string) random_int(100000, 999999);

        Cache::put($this->passwordResetCacheKey($email), [
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'created_at' => now()->toDateTimeString(),
        ], now()->addMinutes(10));

        Mail::raw(
            "Kode verifikasi reset kata sandi Anda adalah: {$otp}\n\nKode ini berlaku selama 10 menit. Abaikan email ini jika Anda tidak meminta reset kata sandi.",
            function ($message) use ($email) {
                $message->to($email)
                    ->subject('Kode Reset Kata Sandi');
            }
        );

        return back()
            ->withInput(['email' => $email])
            ->with('reset_email', $email)
            ->with('success', 'Kode verifikasi telah dikirim ke email Anda.');
    }

    public function resetPasswordWithCode(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.exists' => 'Email tidak terdaftar pada sistem.',
            'otp.required' => 'Kode verifikasi wajib diisi.',
            'otp.digits' => 'Kode verifikasi harus 6 digit.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sesuai.',
        ]);

        $email = strtolower($validated['email']);
        $cacheKey = $this->passwordResetCacheKey($email);
        $payload = Cache::get($cacheKey);

        if (!$payload || !Hash::check($validated['otp'], $payload['otp_hash'] ?? '')) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['otp' => 'Kode verifikasi salah atau sudah kedaluwarsa.']);
        }

        $user = User::where('email', $email)->first();

        if (!$user || !$user->is_active) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun pengguna tidak aktif atau tidak ditemukan.']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        Cache::forget($cacheKey);

        return redirect()
            ->route('login')
            ->with('success', 'Kata sandi berhasil diatur ulang. Silakan login menggunakan kata sandi baru.');
    }

    protected function passwordResetCacheKey(string $email): string
    {
        return 'password_reset_otp:' . strtolower($email);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Berhasil logout.');
    }

    protected function redirectByRole(Request $request, User $user)
    {
        return match ($user->role) {
            'admin_sistem' => redirect()->route('admin.system.dashboard'),

            'admin_provinsi',
            'admin_kabkota' => redirect()->route('tenant.dashboard'),

            'pegawai' => redirect()->route('employee.dashboard'),

            'pimpinan' => redirect()->route('leader.dashboard'),

            default => (function () use ($request) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()
                    ->route('login')
                    ->with('error', 'Dashboard untuk role ini belum tersedia.');
            })(),
        };
    }
}