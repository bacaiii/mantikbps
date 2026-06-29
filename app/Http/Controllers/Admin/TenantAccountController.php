<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantAccountController extends Controller
{
    public function index()
    {
        $accounts = User::with('tenant')
            ->whereIn('role', ['admin_provinsi', 'admin_kabkota'])
            ->latest()
            ->paginate(10);

        return view('admin.tenant_accounts.index', compact('accounts'));
    }

    public function create()
    {
        return view('admin.tenant_accounts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        $this->validateWilayahByType($validated['tipe_wilayah'], $validated['wilayah']);

        $password = $validated['password'] ?: $this->generateRandomPassword(8);
        $loginId = $validated['login_id'] ?: $this->generateUniqueLoginId($validated['wilayah']);
        $tenantCode = $validated['kode_wilayah'];
        $role = $this->resolveRoleFromSelectedType($validated['tipe_wilayah']);
        $actualTenantType = $this->resolveActualTenantType($validated['tipe_wilayah'], $validated['wilayah']);

        DB::transaction(function () use ($validated, $password, $loginId, $tenantCode, $role, $actualTenantType) {
            $tenant = Tenant::create([
                'code' => $tenantCode,
                'name' => $validated['nama_bps'],
                'wilayah' => $validated['wilayah'],
                'type' => $actualTenantType,
                'is_active' => true,
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $validated['nama_bps'],
                'login_id' => $loginId,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'role' => $role,
                'password' => Hash::make($password),
                'password_text' => Crypt::encryptString($password),
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('admin.system.tenant-accounts.index')
            ->with('success', 'Akun tenant berhasil dibuat. Password: ' . $password);
    }

    public function edit(User $user)
    {
        $this->ensureTenantAdmin($user);

        return view('admin.tenant_accounts.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureTenantAdmin($user);

        $validated = $request->validate($this->rules($user), $this->messages());

        $this->validateWilayahByType($validated['tipe_wilayah'], $validated['wilayah']);

        $loginId = $validated['login_id'] ?: $this->generateUniqueLoginId($validated['wilayah'], $user->id);
        $tenantCode = $validated['kode_wilayah'];
        $role = $this->resolveRoleFromSelectedType($validated['tipe_wilayah']);
        $actualTenantType = $this->resolveActualTenantType($validated['tipe_wilayah'], $validated['wilayah']);

        DB::transaction(function () use ($validated, $user, $loginId, $tenantCode, $role, $actualTenantType) {
            $tenant = $user->tenant;

            if (!$tenant) {
                $tenant = Tenant::create([
                    'code' => $tenantCode,
                    'name' => $validated['nama_bps'],
                    'wilayah' => $validated['wilayah'],
                    'type' => $actualTenantType,
                    'is_active' => true,
                ]);

                $user->tenant_id = $tenant->id;
            } else {
                $tenant->update([
                    'code' => $tenantCode,
                    'name' => $validated['nama_bps'],
                    'wilayah' => $validated['wilayah'],
                    'type' => $actualTenantType,
                ]);
            }

            $user->name = $validated['nama_bps'];
            $user->login_id = $loginId;
            $user->email = $validated['email'];
            $user->phone = $validated['phone'];
            $user->role = $role;

            if (!empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
                $user->password_text = Crypt::encryptString($validated['password']);
            }

            $user->save();
        });

        return redirect()
            ->route('admin.system.tenant-accounts.index')
            ->with('success', 'Akun tenant berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $this->ensureTenantAdmin($user);

        DB::transaction(function () use ($user) {
            $tenant = $user->tenant;

            $user->delete();

            if ($tenant && $tenant->users()->count() === 0) {
                $tenant->delete();
            }
        });

        return redirect()
            ->route('admin.system.tenant-accounts.index')
            ->with('success', 'Akun tenant berhasil dihapus.');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'rekap-akun-bps-' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ];

        $callback = function () {
            $handle = fopen('php://output', 'w');

            // BOM UTF-8 agar Excel tidak rusak karakter
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, [
                'ID Login',
                'Email',
                'Nama BPS',
                'Wilayah',
                'Kode Wilayah',
                'Tipe Wilayah',
                'Nomor HP',
                'Role',
                'Password',
                'Dibuat Pada',
            ]);

            User::with('tenant')
                ->whereIn('role', ['admin_provinsi', 'admin_kabkota'])
                ->latest()
                ->chunk(200, function ($users) use ($handle) {
                    foreach ($users as $user) {
                        fputcsv($handle, [
                            $user->login_id,
                            $user->email,
                            optional($user->tenant)->name,
                            optional($user->tenant)->wilayah,
                            optional($user->tenant)->code,
                            optional($user->tenant)->type ? ucfirst(optional($user->tenant)->type) : '-',
                            $user->phone,
                            $user->role === 'admin_provinsi' ? 'Admin Provinsi' : 'Admin Kabupaten/Kota',
                            $user->password_preview,
                            optional($user->created_at)?->format('d-m-Y H:i'),
                        ]);
                    }
                });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    protected function ensureTenantAdmin(User $user): void
    {
        abort_unless(
            in_array($user->role, ['admin_provinsi', 'admin_kabkota']),
            404,
            'Data akun tenant tidak ditemukan.'
        );
    }

    protected function resolveRoleFromSelectedType(string $selectedType): string
    {
        return $selectedType === 'provinsi' ? 'admin_provinsi' : 'admin_kabkota';
    }

    protected function resolveActualTenantType(string $selectedType, string $wilayah): string
    {
        if ($selectedType === 'provinsi') {
            return 'provinsi';
        }

        return Str::lower($wilayah) === 'kota pangkalpinang' ? 'kota' : 'kabupaten';
    }

    protected function rules(?User $user = null): array
    {
        return [
            'nama_bps' => ['required', 'string', 'max:255'],
            'wilayah' => ['required', 'string', Rule::in($this->getAllWilayahOptions())],
            'kode_wilayah' => ['required', 'string', 'max:10', Rule::unique('tenants', 'code')->ignore($user?->tenant?->id)],
            'tipe_wilayah' => ['required', Rule::in(['provinsi', 'kabkota'])],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user?->id)],
            'login_id' => ['nullable', 'string', 'max:255', Rule::unique('users', 'login_id')->ignore($user?->id)],
            'phone' => ['required', 'string', 'max:20'],
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
            'nama_bps.required' => 'Nama BPS wajib diisi.',
            'wilayah.required' => 'Wilayah wajib dipilih.',
            'wilayah.in' => 'Wilayah yang dipilih tidak valid.',
            'kode_wilayah.required' => 'Kode wilayah wajib diisi.',
            'kode_wilayah.unique' => 'Kode wilayah sudah digunakan.',
            'tipe_wilayah.required' => 'Tipe wilayah wajib dipilih.',
            'tipe_wilayah.in' => 'Tipe wilayah harus Provinsi atau Kab/Kota.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'login_id.unique' => 'ID login sudah digunakan.',
            'phone.required' => 'Nomor HP wajib diisi.',
        ];
    }

    protected function getProvinsiOptions(): array
    {
        return [
            'Provinsi Bangka Belitung',
        ];
    }

    protected function getKabKotaOptions(): array
    {
        return [
            'Kabupaten Bangka',
            'Kabupaten Bangka Barat',
            'Kabupaten Bangka Tengah',
            'Kabupaten Bangka Selatan',
            'Kabupaten Belitung',
            'Kabupaten Belitung Timur',
            'Kota Pangkalpinang',
        ];
    }

    protected function getAllWilayahOptions(): array
    {
        return array_merge($this->getProvinsiOptions(), $this->getKabKotaOptions());
    }

    protected function validateWilayahByType(string $selectedType, string $wilayah): void
    {
        $isValid = match ($selectedType) {
            'provinsi' => in_array($wilayah, $this->getProvinsiOptions()),
            'kabkota' => in_array($wilayah, $this->getKabKotaOptions()),
            default => false,
        };

        if (!$isValid) {
            throw ValidationException::withMessages([
                'wilayah' => 'Wilayah tidak sesuai dengan tipe wilayah yang dipilih.',
            ]);
        }
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
        $base = 'bps.' . Str::slug(Str::lower($source), '.');
        $loginId = $base;
        $counter = 1;

        while (
            User::when($ignoreUserId, fn($q) => $q->where('id', '!=', $ignoreUserId))
                ->where('login_id', $loginId)
                ->exists()
        ) {
            $loginId = $base . '.' . $counter;
            $counter++;
        }

        return $loginId;
    }

    protected function generateUniqueTenantCode(string $wilayah, ?int $ignoreTenantId = null): string
    {
        $base = 'TENANT-' . Str::upper(Str::slug($wilayah, '-'));
        $code = $base;
        $counter = 1;

        while (
            Tenant::when($ignoreTenantId, fn($q) => $q->where('id', '!=', $ignoreTenantId))
                ->where('code', $code)
                ->exists()
        ) {
            $code = $base . '-' . $counter;
            $counter++;
        }

        return $code;
    }
}