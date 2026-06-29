<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAccountController extends Controller
{
    protected array $userRoles = [
        'pegawai',
        'pimpinan',
    ];

    public function index(Request $request)
    {
        $query = User::where('tenant_id', auth()->user()->tenant_id)
            ->whereIn('role', $this->userRoles);

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%')
                    ->orWhere('email', 'like', '%' . $request->q . '%')
                    ->orWhere('login_id', 'like', '%' . $request->q . '%');
            });
        }

        $users = $query->latest()->paginate(10)->withQueryString();

        return view('tenant.user_accounts.index', compact('users'));
    }

    public function create()
    {
        return view('tenant.user_accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $password = $validated['password'] ?: $this->generateRandomPassword(8);
        $loginId = $validated['login_id'] ?: $this->generateUniqueLoginId($validated['name']);

        User::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $validated['name'],
            'login_id' => $loginId,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'password' => Hash::make($password),
            'password_text' => Crypt::encryptString($password),
            'is_active' => true,
        ]);

        return redirect()
            ->route('tenant.user-accounts.index')
            ->with('success', 'Akun pengguna berhasil dibuat. Password: ' . $password);
    }

    public function edit(User $user)
    {
        $this->ensureTenantUser($user);

        return view('tenant.user_accounts.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureTenantUser($user);

        $validated = $request->validate($this->rules($user), $this->messages());

        $loginId = $validated['login_id'] ?: $this->generateUniqueLoginId($validated['name'], $user->id);

        DB::transaction(function () use ($validated, $user, $loginId) {
            $user->name = $validated['name'];
            $user->login_id = $loginId;
            $user->email = $validated['email'];
            $user->phone = $validated['phone'];
            $user->role = $validated['role'];

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->password_text = Crypt::encryptString($validated['password']);
            }

            $user->save();
        });

        return redirect()
            ->route('tenant.user-accounts.index')
            ->with('success', 'Akun pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->ensureTenantUser($user);

        $user->delete();

        return redirect()
            ->route('tenant.user-accounts.index')
            ->with('success', 'Akun pengguna berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'rekap-akun-pengguna-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Login',
                'Nama Pengguna',
                'Email',
                'Nomor HP',
                'Jenis Pengguna',
                'Tenant',
                'Wilayah',
                'Password',
                'Dibuat Pada',
            ]);

            User::with('tenant')
                ->where('tenant_id', auth()->user()->tenant_id)
                ->whereIn('role', $this->userRoles)
                ->latest()
                ->chunk(200, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->login_id,
                            $user->name,
                            $user->email,
                            $user->phone,
                            $this->roleLabel($user->role),
                            optional($user->tenant)->name,
                            optional($user->tenant)->wilayah,
                            $user->password_preview,
                            optional($user->created_at)?->format('d-m-Y H:i'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function ensureTenantUser(User $user): void
    {
        abort_unless(
            $user->tenant_id === auth()->user()->tenant_id
            && in_array($user->role, $this->userRoles),
            404,
            'Data akun pengguna tidak ditemukan.'
        );
    }

    protected function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'login_id' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'login_id')->ignore($user?->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'role' => ['required', Rule::in($this->userRoles)],
            'password' => [
                'nullable',
                'string',
                Password::min(6)->letters()->mixedCase()->numbers(),
            ],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'login_id.unique' => 'ID login sudah digunakan.',
            'phone.required' => 'Nomor HP wajib diisi.',
            'role.required' => 'Jenis pengguna wajib dipilih.',
            'role.in' => 'Jenis pengguna tidak valid.',
        ];
    }

    protected function roleLabel(string $role): string
    {
        return match ($role) {
            'pegawai' => 'Pegawai',
            'pimpinan' => 'Pimpinan',
            default => '-',
        };
    }

    protected function generateRandomPassword(int $length = 8): string
    {
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnpqrstuvwxyz';
        $number = '23456789';
        $all = $upper . $lower . $number;

        $password = '';
        $password .= $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $number[random_int(0, strlen($number) - 1)];

        for ($i = 3; $i < $length; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

    protected function generateUniqueLoginId(string $source, ?int $ignoreUserId = null): string
    {
        $base = 'user.' . Str::slug(Str::lower($source), '.');
        $loginId = $base;
        $counter = 1;

        while (
            User::when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
                ->where('login_id', $loginId)
                ->exists()
        ) {
            $loginId = $base . '.' . $counter;
            $counter++;
        }

        return $loginId;
    }
}
