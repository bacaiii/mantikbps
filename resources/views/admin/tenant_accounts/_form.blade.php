@php
    $isEdit = !is_null($account);

    $existingTenantType = optional(optional($account)->tenant)->type;
    $selectedType = old(
        'tipe_wilayah',
        in_array($existingTenantType, ['kabupaten', 'kota']) ? 'kabkota' : $existingTenantType
    );

    $selectedWilayah = old('wilayah', optional(optional($account)->tenant)->wilayah);
    $selectedKodeWilayah = old('kode_wilayah', optional(optional($account)->tenant)->code);
@endphp

<form action="{{ $formAction }}" method="POST">
    @csrf
    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Nama BPS</label>
            <input
                type="text"
                id="nama_bps"
                name="nama_bps"
                class="form-control"
                value="{{ old('nama_bps', optional(optional($account)->tenant)->name) }}"
                placeholder="Contoh: BPS Kabupaten Bangka Barat"
                required
            >
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Tipe Wilayah</label>
            <select name="tipe_wilayah" id="tipe_wilayah" class="form-select" required>
                <option value="">-- Pilih Tipe --</option>
                <option value="provinsi" {{ $selectedType === 'provinsi' ? 'selected' : '' }}>Provinsi</option>
                <option value="kabkota" {{ $selectedType === 'kabkota' ? 'selected' : '' }}>Kab/Kota</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Wilayah</label>
            <select name="wilayah" id="wilayah" class="form-select" required>
                <option value="">-- Pilih tipe wilayah dulu --</option>
            </select>
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Kode Wilayah</label>
            <input
                type="text"
                id="kode_wilayah"
                name="kode_wilayah"
                class="form-control"
                value="{{ $selectedKodeWilayah }}"
                placeholder="Contoh: 1901"
                required
            >
        </div>

        <div class="col-md-3">
            <label class="form-label fw-semibold">Nomor HP</label>
            <input
                type="text"
                name="phone"
                class="form-control"
                value="{{ old('phone', optional($account)->phone) }}"
                placeholder="Contoh: 081234567890"
                required
            >
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">ID Login</label>
            <div class="input-group">
                <input
                    type="text"
                    id="login_id"
                    name="login_id"
                    class="form-control"
                    value="{{ old('login_id', optional($account)->login_id) }}"
                    placeholder="Contoh: bps.kabupaten.bangka.barat"
                >
                <button type="button" class="btn btn-outline-primary" onclick="generateLoginId()">
                    Generate
                </button>
            </div>
            <small class="text-muted">Boleh diketik manual atau digenerate otomatis dari wilayah.</small>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Email</label>
            <input
                type="email"
                name="email"
                class="form-control"
                value="{{ old('email', optional($account)->email) }}"
                placeholder="Contoh: admin.bangkabarat@bps.go.id"
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
                        value="{{ $account->password_preview }}"
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

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('admin.system.tenant-accounts.index') }}" class="btn btn-light border">
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
    const kodeWilayahMap = {
        'Provinsi Bangka Belitung': '1900',
        'Kabupaten Bangka': '1901',
        'Kabupaten Belitung': '1902',
        'Kabupaten Bangka Barat': '1903',
        'Kabupaten Bangka Tengah': '1904',
        'Kabupaten Bangka Selatan': '1905',
        'Kabupaten Belitung Timur': '1906',
        'Kota Pangkalpinang': '1971'
    };

    const wilayahMap = {
        provinsi: [
            'Provinsi Bangka Belitung'
        ],
        kabkota: [
            'Kabupaten Bangka',
            'Kabupaten Bangka Barat',
            'Kabupaten Bangka Tengah',
            'Kabupaten Bangka Selatan',
            'Kabupaten Belitung',
            'Kabupaten Belitung Timur',
            'Kota Pangkalpinang'
        ]
    };

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
        const wilayah = document.getElementById('wilayah').value || 'bps';
        document.getElementById('login_id').value = 'bps.' + simpleSlug(wilayah);
    }

    function syncKodeWilayah(force = false) {
        const wilayah = document.getElementById('wilayah');
        const kodeWilayah = document.getElementById('kode_wilayah');

        if (!wilayah || !kodeWilayah) {
            return;
        }

        const defaultKode = kodeWilayahMap[wilayah.value] || '';

        if (force || !kodeWilayah.value) {
            kodeWilayah.value = defaultKode;
        }
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

    function populateWilayah(selectedValue = '') {
        const tipeWilayah = document.getElementById('tipe_wilayah');
        const wilayah = document.getElementById('wilayah');
        const selectedType = tipeWilayah.value;

        wilayah.innerHTML = '';

        if (!selectedType || !wilayahMap[selectedType]) {
            wilayah.disabled = true;
            wilayah.innerHTML = '<option value="">-- Pilih tipe wilayah dulu --</option>';
            return;
        }

        wilayah.disabled = false;
        wilayah.innerHTML = '<option value="">-- Pilih Wilayah --</option>';

        wilayahMap[selectedType].forEach(item => {
            const option = document.createElement('option');
            option.value = item;
            option.textContent = item;

            if (item === selectedValue) {
                option.selected = true;
            }

            wilayah.appendChild(option);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tipeWilayah = document.getElementById('tipe_wilayah');
        const wilayah = document.getElementById('wilayah');
        const selectedWilayah = @json($selectedWilayah);

        populateWilayah(selectedWilayah);
        syncKodeWilayah(false);

        tipeWilayah.addEventListener('change', function () {
            populateWilayah('');
            syncKodeWilayah(true);
        });

        wilayah.addEventListener('change', function () {
            syncKodeWilayah(true);
            if (!document.getElementById('login_id').value) {
                generateLoginId();
            }
        });

        @if(!$isEdit)
            if (!document.getElementById('password').value) {
                generatePassword();
            }
        @endif
    });
</script>
@endpush