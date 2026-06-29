@php
    $dateValue = function ($field) use ($publication) {
        $value = old($field, $publication->{$field} ?? null);

        if (!$value) {
            return '';
        }

        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->format('Y-m-d');
        }

        return \Illuminate\Support\Carbon::parse($value)->format('Y-m-d');
    };
@endphp

<form id="publicationForm" action="{{ $formAction }}" method="POST">
    @csrf

    @if($formMethod !== 'POST')
        @method($formMethod)
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Data belum dapat disimpan.</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-info" style="background: rgba(13, 202, 240, 0.15); border: 1px solid rgba(13, 202, 240, 0.35);">
        <strong>Catatan jadwal:</strong><br>
        Pada form tambah publikasi, tanggal yang sudah lewat tidak dapat dipilih. Pada form edit, tanggal lampau tetap dapat dipilih untuk memperbaiki data lama. <strong>Mulai Penyusunan</strong> boleh sama dengan <strong>Mulai Pemeriksaan</strong>, tetapi tidak boleh melewati jadwal pemeriksaan. Keduanya tetap maksimal H-1 sebelum <strong>Upload ke Portal</strong>. Jadwal Upload ke Portal maksimal H-3 sebelum <strong>Rilis Publikasi</strong>.
    </div>

    <div class="row g-3">
        <div class="col-md-8">
            <label class="form-label fw-semibold">Nama Publikasi</label>
            <input type="text"
                name="nama_publikasi"
                class="form-control @error('nama_publikasi') is-invalid @enderror"
                value="{{ old('nama_publikasi', $publication->nama_publikasi) }}"
                placeholder="Contoh: Provinsi Kepulauan Bangka Belitung Dalam Angka 2026"
                required>

            @error('nama_publikasi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Estimasi Nomor Publikasi</label>
            <input type="text"
                name="estimasi_nomor_publikasi"
                class="form-control @error('estimasi_nomor_publikasi') is-invalid @enderror"
                value="{{ old('estimasi_nomor_publikasi', $publication->estimasi_nomor_publikasi) }}"
                placeholder="Contoh: 19000.25001">

            @error('estimasi_nomor_publikasi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Kategori</label>
            <select name="kategori" class="form-select @error('kategori') is-invalid @enderror" required>
                <option value="">-- Pilih Kategori --</option>
                <option value="ARC" {{ old('kategori', $publication->kategori) === 'ARC' ? 'selected' : '' }}>ARC</option>
                <option value="Non-ARC" {{ old('kategori', $publication->kategori) === 'Non-ARC' ? 'selected' : '' }}>Non-ARC</option>
            </select>

            @error('kategori')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Periode</label>
            <select name="periode" class="form-select @error('periode') is-invalid @enderror" required>
                <option value="">-- Pilih Periode --</option>
                @foreach(['Bulanan', 'Triwulanan', 'Semesteran', 'Tahunan', 'Lainnya'] as $periode)
                    <option value="{{ $periode }}" {{ old('periode', $publication->periode) === $periode ? 'selected' : '' }}>{{ $periode }}</option>
                @endforeach
            </select>

            @error('periode')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Akurasi Publikasi</label>
            <select name="akurasi_publikasi" class="form-select @error('akurasi_publikasi') is-invalid @enderror">
                <option value="">-- Pilih Akurasi --</option>
                <option value="RSE" {{ old('akurasi_publikasi', $publication->akurasi_publikasi) === 'RSE' ? 'selected' : '' }}>RSE</option>
                <option value="Non-RSE" {{ old('akurasi_publikasi', $publication->akurasi_publikasi) === 'Non-RSE' ? 'selected' : '' }}>Non-RSE</option>
            </select>

            @error('akurasi_publikasi')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Jadwal Rilis Publikasi</label>
            <input type="date"
                   name="jadwal_rilis"
                   id="jadwal_rilis"
                   placeholder="dd/mm/yyyy"
                   class="form-control @error('jadwal_rilis') is-invalid @enderror"
                   value="{{ $dateValue('jadwal_rilis') }}"
                   required>
            @error('jadwal_rilis')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Jadwal Upload ke Portal</label>
            <input type="date"
                   name="jadwal_upload"
                   id="jadwal_upload"
                   placeholder="dd/mm/yyyy"
                   class="form-control @error('jadwal_upload') is-invalid @enderror"
                   value="{{ $dateValue('jadwal_upload') }}"
                   required>
            <small class="text-muted d-block mt-1" id="uploadHelp">Maksimal H-3 sebelum Jadwal Rilis Publikasi.</small>
            @error('jadwal_upload')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Jadwal Mulai Pemeriksaan</label>
            <input type="date"
                   name="jadwal_mulai_pemeriksaan"
                   id="jadwal_mulai_pemeriksaan"
                   placeholder="dd/mm/yyyy"
                   class="form-control @error('jadwal_mulai_pemeriksaan') is-invalid @enderror"
                   value="{{ $dateValue('jadwal_mulai_pemeriksaan') }}"
                   required>
            <small class="text-muted d-block mt-1" id="reviewHelp">Maksimal H-1 sebelum Jadwal Upload ke Portal.</small>
            @error('jadwal_mulai_pemeriksaan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-6">
            <label class="form-label fw-semibold">Jadwal Mulai Penyusunan</label>
            <input type="date"
                   name="jadwal_mulai_penyusunan"
                   id="jadwal_mulai_penyusunan"
                   placeholder="dd/mm/yyyy"
                   class="form-control @error('jadwal_mulai_penyusunan') is-invalid @enderror"
                   value="{{ $dateValue('jadwal_mulai_penyusunan') }}"
                   required>
            <small class="text-muted d-block mt-1" id="startHelp">Maksimal sama dengan Jadwal Mulai Pemeriksaan dan tetap H-1 sebelum Jadwal Upload ke Portal.</small>
            @error('jadwal_mulai_penyusunan')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">Wilayah</label>
            <input type="text"
                   class="form-control"
                   value="{{ old('wilayah', $publication->wilayah ?? optional(auth()->user()->tenant)->wilayah) }}"
                   readonly>
        </div>

        @if($publication->exists)
            <div class="col-md-4">
                <label class="form-label fw-semibold">Status Saat Ini</label>
                <input type="text" class="form-control" value="{{ $publication->status_label }}" readonly>
                <small class="text-muted">Status berjalan otomatis sesuai proses kerja.</small>
            </div>
        @endif

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('tenant.publications.index') }}" class="btn btn-light border">Kembali</a>
            <button type="submit" class="btn btn-primary">{{ $submitLabel }}</button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    function parseDateInput(dateString) {
        return new Date(dateString + 'T00:00:00');
    }

    function subtractDays(dateString, days) {
        const date = parseDateInput(dateString);
        date.setDate(date.getDate() - days);
        return date;
    }

    function formatDateInput(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDateIndo(date) {
        return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const releaseInput = document.getElementById('jadwal_rilis');
        const uploadInput = document.getElementById('jadwal_upload');
        const reviewInput = document.getElementById('jadwal_mulai_pemeriksaan');
        const startInput = document.getElementById('jadwal_mulai_penyusunan');
        const uploadHelp = document.getElementById('uploadHelp');
        const reviewHelp = document.getElementById('reviewHelp');
        const startHelp = document.getElementById('startHelp');

        if (!releaseInput || !uploadInput || !reviewInput || !startInput) {
            return;
        }

        const today = formatDateInput(new Date());
        const isEditForm = @json($publication->exists);

        if (!isEditForm) {
            [releaseInput, uploadInput, reviewInput, startInput].forEach(function (input) {
                input.min = today;
            });
        }

        function clearIfOutOfLimit(input) {
            if (input.value && input.min && input.value < input.min) {
                input.value = '';
            }

            if (input.value && input.max && input.value > input.max) {
                input.value = '';
            }
        }

        function refreshDateLimits(clearInvalidValues = true) {
            if (releaseInput.value) {
                const latestUpload = subtractDays(releaseInput.value, 3);
                uploadInput.max = formatDateInput(latestUpload);
                if (uploadHelp) {
                    uploadHelp.textContent = 'Tanggal upload terakhir yang dapat dipilih: ' + formatDateIndo(latestUpload) + '.';
                }
            } else {
                uploadInput.removeAttribute('max');
            }

            let latestBeforeUpload = null;

            if (uploadInput.value) {
                latestBeforeUpload = subtractDays(uploadInput.value, 1);
                reviewInput.max = formatDateInput(latestBeforeUpload);

                if (reviewHelp) {
                    reviewHelp.textContent = 'Tanggal mulai pemeriksaan terakhir yang dapat dipilih: ' + formatDateIndo(latestBeforeUpload) + '.';
                }
            } else {
                reviewInput.removeAttribute('max');
            }

            if (reviewInput.value) {
                const reviewDate = parseDateInput(reviewInput.value);
                let latestStart = reviewDate;

                if (latestBeforeUpload && latestBeforeUpload < latestStart) {
                    latestStart = latestBeforeUpload;
                }

                startInput.max = formatDateInput(latestStart);

                if (startHelp) {
                    startHelp.textContent = 'Tanggal mulai penyusunan terakhir yang dapat dipilih: ' + formatDateIndo(latestStart) + '.';
                }
            } else if (latestBeforeUpload) {
                startInput.max = formatDateInput(latestBeforeUpload);

                if (startHelp) {
                    startHelp.textContent = 'Tanggal mulai penyusunan terakhir yang dapat dipilih: ' + formatDateIndo(latestBeforeUpload) + '.';
                }
            } else {
                startInput.removeAttribute('max');
            }

            if (clearInvalidValues) {
                [uploadInput, reviewInput, startInput].forEach(clearIfOutOfLimit);
            }
        }

        releaseInput.addEventListener('change', refreshDateLimits);
        uploadInput.addEventListener('change', refreshDateLimits);
        reviewInput.addEventListener('change', refreshDateLimits);
        startInput.addEventListener('change', refreshDateLimits);
        refreshDateLimits(false);
    });
</script>
@endpush
