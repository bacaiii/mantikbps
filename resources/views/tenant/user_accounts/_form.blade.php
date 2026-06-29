@php
    $isEdit = !is_null($userAccount);
@endphp

<form action="{{ $formAction }}" method="POST">
    @csrf

    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold">Nama Pengguna</label>
            <input
                type="text"
                id="name"
                name="name"
                class="form-control"
                value="{{ old('name', optional($userAccount)->name) }}"
                placeholder="Contoh: Teuku Umar"
                required
            >
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Jenis Pengguna</label>
            <select name="role" class="form-select" required>
                <option value="">-- Pilih Jenis Pengguna --</option>
                <option value="pegawai" {{ old('role', optional($userAccount)->role) === 'pegawai' ? 'selected' : '' }}>
                    Pegawai
                </option>
                <option value="pimpinan" {{ old('role', optional($userAccount)->role) === 'pimpinan' ? 'selected' : '' }}>
                    Pimpinan
                </option>
            </select>
            <small class="text-muted">Tugas detail pegawai akan ditentukan di menu Atur Tim Kerja.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">ID Login</label>
            <div class="input-group">
                <input
                    type="text"
                    id="login_id"
                    name="login_id"
                    class="form-control"
                    value="{{ old('login_id', optional($userAccount)->login_id) }}"
                    placeholder="Contoh: user.teuku.umar"
                >
                <button type="button" class="btn btn-outline-primary" onclick="generateLoginId()">
                    Generate
                </button>
            </div>
            <small class="text-muted">Ketik manual atau digenerate otomatis dari nama.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input
                type="email"
                name="email"
                class="form-control"
                value="{{ old('email', optional($userAccount)->email) }}"
                placeholder="Contoh: umar@bps.go.id"
                required
            >
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Nomor HP</label>
            <input
                type="text"
                name="phone"
                class="form-control"
                value="{{ old('phone', optional($userAccount)->phone) }}"
                placeholder="Contoh: 081234567890"
                required
            >
        </div>

        @if($isEdit)
            <div class="col-md-6">
                <label class="form-label fw-semibold">Password Saat Ini</label>
                <div class="input-group">
                    <input
                        type="password"
                        id="current_password"
                        class="form-control"
                        value="{{ $userAccount->password_preview }}"
                        readonly
                    >
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('current_password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        @endif

        <div class="col-md-6">
            <label class="form-label fw-semibold">{{ $isEdit ? 'Password Baru (opsional)' : 'Password' }}</label>
            <div class="password-action-row">
                <div class="input-group password-eye-group">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        value="{{ old('password') }}"
                        placeholder="Minimal 6 karakter"
                    >
                    <button type="button" class="btn btn-outline-secondary password-eye-btn" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <button type="button" class="btn btn-outline-primary password-random-btn" onclick="generatePassword()">
                    <i class="bi bi-shuffle me-1"></i> Acak Password
                </button>
            </div>
            <small class="text-muted">Minimal 6 karakter, wajib ada huruf besar, huruf kecil, dan angka.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Wilayah Kerja</label>
            <input
                type="text"
                class="form-control"
                value="{{ optional(auth()->user()->tenant)->name }} - {{ optional(auth()->user()->tenant)->wilayah }}"
                readonly
            >
            <small class="text-muted">Akun pengguna otomatis masuk ke wilayah kerja Anda.</small>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('tenant.user-accounts.index') }}" class="btn btn-light border">
                Kembali
            </a>

            <button type="submit" class="btn btn-primary">
                {{ $submitLabel }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function simpleSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, '.')
            .replace(/\.+/g, '.')
            .replace(/^-+|-+$/g, '');
    }

    function generateLoginId() {
        const name = document.getElementById('name').value || 'pengguna';
        document.getElementById('login_id').value = 'user.' + simpleSlug(name);
    }

    function generatePassword() {
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghijkmnpqrstuvwxyz';
        const number = '23456789';
        const all = upper + lower + number;

        let password =
            upper[Math.floor(Math.random() * upper.length)] +
            lower[Math.floor(Math.random() * lower.length)] +
            number[Math.floor(Math.random() * number.length)];

        for (let i = 0; i < 5; i++) {
            password += all[Math.floor(Math.random() * all.length)];
        }

        password = password.split('').sort(() => Math.random() - 0.5).join('');
        document.getElementById('password').value = password;
    }

    document.addEventListener('DOMContentLoaded', function () {
        @if(!$isEdit)
            if (!document.getElementById('password').value) {
                generatePassword();
            }
        @endif
    });
</script>
@endpush
