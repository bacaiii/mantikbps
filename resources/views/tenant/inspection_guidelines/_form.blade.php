@php
    $selectedType = old('type', $guideline->type ?? 'konten');
    $selectedAnatomy = old('anatomy_section', $guideline->anatomy_section ?? ($selectedType === 'layout' ? 'Depan' : 'Isi'));
    $selectedItem = old('inspection_item', $guideline->inspection_item ?? '');
    $isEditMode = ($formMethod ?? 'POST') !== 'POST';
    $contextLocked = $isEditMode || request()->filled('anatomy_section') || request()->filled('inspection_item');
    $typeLabel = $selectedType === 'layout' ? 'Pemeriksaan Layout' : 'Pemeriksaan Konten';
@endphp

<form action="{{ $formAction }}" method="POST" class="guideline-form" id="guidelineForm">
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

    <div class="alert guideline-info-alert mb-4">
        <div class="d-flex gap-3">
            <i class="bi bi-info-circle-fill fs-4 text-primary"></i>
            <div>
                <strong>Petunjuk pengisian:</strong><br>
                {{ $contextLocked
                    ? 'Konteks pedoman sudah mengikuti card yang dipilih. Admin cukup mengisi atau memperbarui rincian pemeriksaan.'
                    : 'Pilih konteks pedoman terlebih dahulu, lalu tuliskan rincian pemeriksaan yang akan tampil sebagai checklist Ya/Tidak pada menu pemeriksaan pegawai.' }}
            </div>
        </div>
    </div>

    @if($contextLocked)
        <input type="hidden" name="type" value="{{ $selectedType }}">
        <input type="hidden" name="anatomy_section" value="{{ $selectedAnatomy }}">
        <input type="hidden" name="inspection_item" value="{{ $selectedItem }}">

        <div class="guideline-context-grid mb-4">
            <div class="guideline-context-card">
                <span class="context-icon primary"><i class="bi bi-ui-checks-grid"></i></span>
                <div>
                    <small>Jenis Pedoman</small>
                    <strong>{{ $typeLabel }}</strong>
                </div>
            </div>
            <div class="guideline-context-card">
                <span class="context-icon info"><i class="bi bi-diagram-3"></i></span>
                <div>
                    <small>Anatomi</small>
                    <strong>{{ $selectedAnatomy ?: '-' }}</strong>
                </div>
            </div>
            <div class="guideline-context-card">
                <span class="context-icon success"><i class="bi bi-card-checklist"></i></span>
                <div>
                    <small>Nama Card</small>
                    <strong>{{ $selectedItem ?: '-' }}</strong>
                </div>
            </div>
        </div>
    @endif

    <div class="row g-3">
        @unless($contextLocked)
            <div class="col-md-4">
                <label class="form-label fw-semibold">Jenis Pedoman</label>
                <select name="type" id="guidelineType" class="form-select @error('type') is-invalid @enderror" required>
                    <option value="konten" {{ $selectedType === 'konten' ? 'selected' : '' }}>Pemeriksaan Konten</option>
                    <option value="layout" {{ $selectedType === 'layout' ? 'selected' : '' }}>Pemeriksaan Layout</option>
                </select>
                @error('type')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Anatomi Publikasi</label>
                <input type="text"
                       name="anatomy_section"
                       id="guidelineAnatomy"
                       class="form-control @error('anatomy_section') is-invalid @enderror"
                       value="{{ $selectedAnatomy }}"
                       list="guidelineAnatomyOptions"
                       placeholder="Contoh: Isi / Depan / Pendahuluan / Penutup"
                       required>
                <datalist id="guidelineAnatomyOptions"></datalist>
                @error('anatomy_section')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-4">
                <label class="form-label fw-semibold">Nama Card / Bagian Pemeriksaan</label>
                <input type="text"
                       name="inspection_item"
                       id="guidelineItem"
                       class="form-control @error('inspection_item') is-invalid @enderror"
                       value="{{ $selectedItem }}"
                       list="guidelineItemOptions"
                       placeholder="Contoh: Pembatas Bab / Kover Depan / Daftar Isi"
                       required>
                <datalist id="guidelineItemOptions"></datalist>
                @error('inspection_item')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        @endunless

        <div class="col-md-12">
            <label class="form-label fw-semibold">Rincian Pemeriksaan</label>
            <textarea name="requirement_detail"
                      class="form-control @error('requirement_detail') is-invalid @enderror"
                      rows="8"
                      placeholder="Tuliskan rincian pemeriksaan sesuai pedoman publikasi."
                      required>{{ old('requirement_detail', $guideline->requirement_detail) }}</textarea>
            @error('requirement_detail')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-md-12">
            <div class="form-check guideline-active-check">
                <input type="checkbox"
                       name="is_active"
                       value="1"
                       class="form-check-input"
                       id="is_active"
                       {{ old('is_active', $guideline->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">
                    Aktifkan rincian ini pada form pemeriksaan pegawai
                </label>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-end gap-2 mt-4">
            <a href="{{ route('tenant.inspection-guidelines.index', ['type' => $selectedType]) }}" class="btn btn-light border">Kembali</a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save me-1"></i> {{ $submitLabel }}
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const structures = @json($structures);
        const typeInput = document.getElementById('guidelineType');
        const anatomyInput = document.getElementById('guidelineAnatomy');
        const itemInput = document.getElementById('guidelineItem');
        const anatomyOptions = document.getElementById('guidelineAnatomyOptions');
        const itemOptions = document.getElementById('guidelineItemOptions');

        if (!typeInput || !anatomyInput || !itemInput || !anatomyOptions || !itemOptions) {
            return;
        }

        function rebuildOptions() {
            const type = typeInput.value || 'konten';
            const anatomyMap = structures[type] || {};
            const anatomyList = Object.keys(anatomyMap);

            anatomyOptions.innerHTML = '';
            anatomyList.forEach(function (anatomy) {
                const option = document.createElement('option');
                option.value = anatomy;
                anatomyOptions.appendChild(option);
            });

            if (!anatomyInput.value || !anatomyMap[anatomyInput.value]) {
                anatomyInput.value = anatomyList[0] || '';
            }

            rebuildItemOptions();
        }

        function rebuildItemOptions() {
            const type = typeInput.value || 'konten';
            const anatomyMap = structures[type] || {};
            const items = anatomyMap[anatomyInput.value] || [];

            itemOptions.innerHTML = '';
            items.forEach(function (item) {
                const option = document.createElement('option');
                option.value = item;
                itemOptions.appendChild(option);
            });

            if (!itemInput.value && items.length) {
                itemInput.value = items[0];
            }
        }

        typeInput.addEventListener('change', function () {
            anatomyInput.value = '';
            itemInput.value = '';
            rebuildOptions();
        });

        anatomyInput.addEventListener('change', function () {
            itemInput.value = '';
            rebuildItemOptions();
        });

        rebuildOptions();
    });
</script>
@endpush
