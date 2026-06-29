@extends('layouts.tenant')

@section('title', 'Bantuan Upload Tim Penyusun')

@section('content')
    @php
        $isProvinsiTenant = optional($publication->tenant)->type === 'provinsi';

        $documentConfigs = [
            'naskah_pdf' => [
                'title' => 'Upload Naskah Publikasi PDF',
                'subtitle' => 'Naskah utama untuk pemeriksa konten dan layout.',
                'icon' => 'bi-file-earmark-pdf',
                'accept' => '.pdf',
                'help' => 'Format PDF. Maksimal 20MB.',
                'multiple' => false,
            ],
            'naskah_zip' => [
                'title' => 'Upload Naskah Publikasi RAR/ZIP',
                'subtitle' => 'File sumber naskah yang dapat diunduh pemeriksa.',
                'icon' => 'bi-file-earmark-zip',
                'accept' => '.zip,.rar',
                'help' => 'Format ZIP atau RAR. Maksimal 50MB.',
                'multiple' => false,
            ],
            'infografis' => [
                'title' => 'Upload File Infografis',
                'subtitle' => $isProvinsiTenant ? 'File yang akan diperiksa oleh Operator Infografis.' : 'Opsional untuk BPS kabupaten/kota.',
                'icon' => 'bi-image',
                'accept' => '.jpg,.jpeg',
                'help' => 'Format JPG/JPEG. Maksimal 500KB per file.',
                'multiple' => true,
            ],
            'daftar_tabel_gambar' => [
                'title' => 'Upload Daftar Tabel & Gambar',
                'subtitle' => $isProvinsiTenant ? 'Daftar tabel dan gambar dalam format Excel.' : 'Opsional untuk BPS kabupaten/kota.',
                'icon' => 'bi-file-earmark-spreadsheet',
                'accept' => '.xls,.xlsx,.csv',
                'help' => 'Format XLS, XLSX, atau CSV. Maksimal 10MB.',
                'multiple' => false,
            ],
        ];

        $completionLabels = [
            'naskah_pdf' => 'Naskah PDF',
            'naskah_zip' => 'Naskah RAR/ZIP',
            'sprp' => 'Form SPRP',
        ];

        if ($isProvinsiTenant) {
            $completionLabels['infografis'] = 'Infografis';
            $completionLabels['daftar_tabel_gambar'] = 'Daftar Tabel & Gambar';
        }

        $sprpOldBool = function ($field, $default = null) use ($sprp) {
            $value = old($field, optional($sprp)->{$field} ?? $default);
            if ($value === null) return '';
            return (string) ((int) (bool) $value);
        };

        $sprpBahasa = old('bahasa', optional($sprp)->bahasa ?? ['Indonesia']);
        $ukuranOptions = ['B5 ISO', 'B5 JIS', 'A5', 'A4'];
        $currentUkuran = old('ukuran', optional($sprp)->ukuran);
        $selectedUkuran = in_array($currentUkuran, $ukuranOptions, true) ? $currentUkuran : ($currentUkuran ? 'Lainnya' : '');
        $ukuranLainnya = old('ukuran_lainnya', $selectedUkuran === 'Lainnya' ? $currentUkuran : '');
    @endphp

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-0 fw-bold">Bantuan Upload Tim Penyusun</h5>
                    <small class="text-muted">{{ $publication->nama_publikasi }}</small>
                </div>
                <span class="status-chip {{ $publication->status_css_class }}">{{ $publication->status_label }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="alert alert-info mb-4">
                <strong>Keterangan:</strong><br>
                Halaman <strong>Tim Penyusun</strong> bertujuan sebagai form bantuan admin untuk mengunggah dokumen penyusunan apabila tim penyusun berkendala. Tampilan dan jenis file menyesuaikan dengan menu penyusun milik pegawai.
            </div>

            <div class="completion-panel mb-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-2">
                    <div>
                        <h5 class="mb-0 fw-bold">Kelengkapan Dokumen Penyusunan</h5>
                        <small class="text-muted">Status kelengkapan file yang sudah tersimpan.</small>
                    </div>
                </div>
                <div class="row g-2 mt-1">
                    @foreach($completionLabels as $key => $label)
                        <div class="col-md-6">
                            <div class="completion-item">
                                <span>{{ $label }}</span>
                                @if($completion[$key] ?? false)
                                    <span class="completion-icon done"><i class="bi bi-check-lg"></i></span>
                                @else
                                    <span class="completion-icon waiting"><i class="bi bi-hourglass-split"></i></span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if(!$canEditAuthorDocuments)
                    <div class="alert alert-warning mt-3 mb-0">
                        Bantuan upload dikunci karena publikasi tidak berada pada tahap <strong>Penyusunan/Revisi</strong>. Saat ini status publikasi adalah <strong>{{ $publication->status_label }}</strong>.
                    </div>
                @endif
            </div>

            <div class="author-input-box mb-4">
                <div class="author-input-head">
                    <div>
                        <h5 class="mb-0 fw-bold">Upload Dokumen Tim Penyusun</h5>
                        <small class="text-muted">Admin dapat membantu mengunggah dokumen per jenis file.</small>
                    </div>
                </div>

                <div class="author-input-body author-input-card-list">
                    @foreach($documentConfigs as $type => $config)
                        @php
                            $items = $documentsByType->get($type, collect())->sortByDesc('version');
                            $isDone = $items->isNotEmpty();
                        @endphp

                        <div class="author-upload-card admin-author-upload-card">
                            <div class="author-upload-status-corner">
                                @if($isDone)
                                    <span class="upload-status-pill done"><i class="bi bi-check-circle"></i> Lengkap</span>
                                @else
                                    <span class="upload-status-pill waiting"><i class="bi bi-hourglass-split"></i> Belum</span>
                                @endif
                            </div>

                            <div class="author-upload-title-line">
                                <span class="author-input-icon"><i class="bi {{ $config['icon'] }}"></i></span>
                                <div class="author-upload-title-text">
                                    <strong>{{ $config['title'] }}</strong>
                                    <small class="text-muted d-block">{{ $config['subtitle'] }}</small>
                                </div>
                            </div>

                            <div class="author-upload-content-grid">
                                <div class="author-upload-left">
                                    @if($canEditAuthorDocuments)
                                        <form action="{{ route('tenant.publication-progress.author-team.upload-document', $publication->id) }}"
                                              method="POST"
                                              enctype="multipart/form-data"
                                              class="author-card-upload-form">
                                            @csrf
                                            <input type="hidden" name="document_type" value="{{ $type }}">

                                            <div class="author-upload-input-line">
                                                <div class="author-file-link-box">
                                                    <label class="author-file-button mb-0">
                                                        Pilih File
                                                        @if($config['multiple'])
                                                            <input type="file" name="files[]" class="author-file-native infographic-input" accept="{{ $config['accept'] }}" multiple>
                                                        @else
                                                            <input type="file" name="file" class="author-file-native" accept="{{ $config['accept'] }}">
                                                        @endif
                                                    </label>

                                                    <input type="url"
                                                           name="external_url"
                                                           class="author-link-inline-input"
                                                           placeholder="atau tempel link dokumen eksternal"
                                                           inputmode="url">
                                                </div>

                                                <button class="btn btn-primary btn-sm author-upload-btn">
                                                    <i class="bi bi-cloud-arrow-up me-1"></i> Simpan
                                                </button>
                                            </div>

                                            @if($config['multiple'])
                                                <div class="infographic-preview-grid mt-2 selected-preview"></div>
                                            @endif
                                        </form>
                                    @else
                                        <div class="author-locked-upload">Input dikunci karena publikasi tidak berada pada tahap penyusunan/revisi.</div>
                                    @endif

                                    <small class="author-upload-help">{{ $config['help'] }}</small>
                                </div>

                                @if($items->isNotEmpty())
                                    <div class="document-version-strip admin-author-version-strip">
                                        @foreach($items as $document)
                                            <div class="document-version-card">
                                                <div class="version-badge">V{{ $document->version }}</div>
                                                <strong>{{ $document->file_original_name }}</strong>
                                                <small>
                                                    {{ $document->readable_size }}<br>
                                                    {{ optional($document->uploader)->name ?? '-' }}<br>
                                                    {{ optional($document->uploaded_at)->format('d-m-Y H:i') }}
                                                </small>
                                                <a href="{{ route('tenant.publication-progress.download-document', $document->id) }}" class="btn btn-outline-primary btn-sm mt-2 w-100" target="{{ $document->is_link ? '_blank' : '_self' }}">
                                                    <i class="bi {{ $document->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $document->is_link ? 'Buka Link' : 'Download' }}
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="sprp-manual-box mb-4">
                <div class="sprp-manual-title">
                    <h5>SURAT PERMINTAAN / PENGESAHAN RANCANGAN PUBLIKASI (SPRP)</h5>
                    <small>Form bantuan admin mengikuti format SPRP menu penyusun.</small>
                </div>

                <form action="{{ route('tenant.publication-progress.author-team.save-sprp', $publication->id) }}" method="POST">
                    @csrf

                    <div class="sprp-manual-table">
                        <div class="sprp-line full"><span class="sprp-no">1.</span><label>Bidang/Bagian</label><div class="sprp-field readonly-field">{{ $publicationTeam->name }}</div></div>

                        <div class="sprp-line full align-top">
                            <span class="sprp-no">2.</span>
                            <label>Rancangan Perwajahan</label>
                            <div class="sprp-field">
                                <select name="rancangan_perwajahan" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}>
                                    <option value="">-- Pilih --</option>
                                    @foreach(['Seksi Diseminasi dan Layanan Statistik', 'subject matter'] as $option)
                                        <option value="{{ $option }}" {{ old('rancangan_perwajahan', optional($sprp)->rancangan_perwajahan) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">Jika dikerjakan subject matter, sertakan rancangan perwajahan dan deskripsi warna/gambar pada dokumen pendukung.</small>
                            </div>
                        </div>

                        <div class="sprp-line full"><span class="sprp-no">3.</span><label>Judul Publikasi</label><div class="sprp-field readonly-field">{{ $publication->nama_publikasi }}</div></div>

                        <div class="sprp-line"><span class="sprp-no">4.</span><label>Apakah Publikasi Baru</label><div class="sprp-field"><select name="publikasi_baru" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih --</option><option value="1" {{ $sprpOldBool('publikasi_baru') === '1' ? 'selected' : '' }}>Ya</option><option value="0" {{ $sprpOldBool('publikasi_baru') === '0' ? 'selected' : '' }}>Tidak</option></select></div></div>
                        <div class="sprp-line"><span class="sprp-no">5.</span><label>Ukuran</label><div class="sprp-field"><select name="ukuran" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih --</option>@foreach($ukuranOptions as $option)<option value="{{ $option }}" {{ $selectedUkuran === $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach<option value="Lainnya" {{ $selectedUkuran === 'Lainnya' ? 'selected' : '' }}>Lainnya</option></select><input type="text" name="ukuran_lainnya" class="form-control form-control-sm mt-2" value="{{ $ukuranLainnya }}" placeholder="Isi ukuran lainnya jika dipilih" {{ !$canEditAuthorDocuments ? 'readonly' : '' }}></div></div>
                        <div class="sprp-line"><span class="sprp-no">6.</span><label>Bentuk Publikasi (Orientasi)</label><div class="sprp-field"><select name="orientasi" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih --</option>@foreach(['Portrait', 'Landscape'] as $option)<option value="{{ $option }}" {{ old('orientasi', optional($sprp)->orientasi) === $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></div></div>
                        <div class="sprp-line"><span class="sprp-no">7.</span><label>Frekuensi Terbit</label><div class="sprp-field readonly-field">{{ $publication->periode ?? '-' }}</div></div>
                        <div class="sprp-line"><span class="sprp-no">8.</span><label>Terbitan yang ke</label><div class="sprp-field"><input type="text" name="terbitan_ke" class="form-control form-control-sm" value="{{ old('terbitan_ke', optional($sprp)->terbitan_ke) }}" placeholder="Contoh: 1" required {{ !$canEditAuthorDocuments ? 'readonly' : '' }}></div></div>
                        <div class="sprp-line"><span class="sprp-no">9.</span><label>Tahun Pertama Kali Terbit</label><div class="sprp-field"><select name="tahun_pertama_terbit" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih Tahun --</option>@foreach($yearOptions as $year)<option value="{{ $year }}" {{ (string) old('tahun_pertama_terbit', optional($sprp)->tahun_pertama_terbit) === (string) $year ? 'selected' : '' }}>{{ $year }}</option>@endforeach</select></div></div>
                        <div class="sprp-line"><span class="sprp-no">10.</span><label>Diterbitkan Untuk</label><div class="sprp-field"><select name="diterbitkan_untuk" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih --</option>@foreach(['Eksternal', 'Internal'] as $option)<option value="{{ $option }}" {{ old('diterbitkan_untuk', optional($sprp)->diterbitkan_untuk) === $option ? 'selected' : '' }}>{{ $option }}</option>@endforeach</select></div></div>
                        <div class="sprp-line full"><span class="sprp-no">11.</span><label>Publikasi dan Tanggal ARC/Non-ARC</label><div class="sprp-field readonly-field">{{ $publication->kategori }}, {{ $publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-' }}</div></div>
                    </div>

                    <div class="sprp-summary-table mt-3">
                        <div class="sprp-summary-label">Keterangan publikasi yang akan dicetak</div>
                        <div class="sprp-summary-cell"><label>Romawi</label><input type="text" name="jumlah_halaman_romawi" class="form-control form-control-sm" value="{{ old('jumlah_halaman_romawi', optional($sprp)->jumlah_halaman_romawi) }}" required {{ !$canEditAuthorDocuments ? 'readonly' : '' }}></div>
                        <div class="sprp-summary-cell"><label>Arab</label><input type="text" name="jumlah_halaman_arab" class="form-control form-control-sm" value="{{ old('jumlah_halaman_arab', optional($sprp)->jumlah_halaman_arab) }}" required {{ !$canEditAuthorDocuments ? 'readonly' : '' }}></div>
                        <div class="sprp-summary-cell wide"><label>Kerja Sama dengan Instansi di Luar BPS</label><select name="kerja_sama_luar_bps" class="form-select form-select-sm" required {{ !$canEditAuthorDocuments ? 'disabled' : '' }}><option value="">-- Pilih --</option><option value="1" {{ $sprpOldBool('kerja_sama_luar_bps') === '1' ? 'selected' : '' }}>Ya</option><option value="0" {{ $sprpOldBool('kerja_sama_luar_bps') === '0' ? 'selected' : '' }}>Tidak</option></select></div>
                        <div class="sprp-summary-cell wide"><label>Bahasa</label><div class="d-flex gap-3 flex-wrap pt-1">@foreach(['Indonesia', 'Inggris'] as $language)<label class="form-check-label small"><input type="checkbox" name="bahasa[]" value="{{ $language }}" class="form-check-input me-1" {{ in_array($language, $sprpBahasa ?? [], true) ? 'checked' : '' }} {{ !$canEditAuthorDocuments ? 'disabled' : '' }}> {{ $language }}</label>@endforeach</div></div>
                    </div>

                    @if($canEditAuthorDocuments)
                        <div class="d-flex justify-content-end mt-3">
                            <button class="btn btn-primary"><i class="bi bi-save me-1"></i> Simpan SPRP</button>
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <a href="{{ route('tenant.publication-progress.index') }}" class="btn btn-light border">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const defaultDocumentLinkPlaceholder = 'atau tempel link dokumen eksternal';

        function updateDocumentLinkPlaceholder(box, fileNames) {
            const linkInput = box ? box.querySelector('.author-link-inline-input') : null;

            if (!linkInput) {
                return;
            }

            if (fileNames.length > 0) {
                const selectedName = fileNames.join(', ');
                linkInput.value = '';
                linkInput.placeholder = selectedName;
                linkInput.title = selectedName;
                linkInput.classList.add('has-selected-file-placeholder');
                linkInput.classList.remove('is-invalid');
            } else {
                linkInput.placeholder = defaultDocumentLinkPlaceholder;
                linkInput.removeAttribute('title');
                linkInput.classList.remove('has-selected-file-placeholder');
            }
        }

        document.querySelectorAll('.author-file-native').forEach(function (input) {
            input.addEventListener('change', function () {
                const box = input.closest('.author-file-link-box');
                const linkInput = box ? box.querySelector('.author-link-inline-input') : null;
                const fileNames = Array.from(input.files || []).map(function (file) { return file.name; });

                updateDocumentLinkPlaceholder(box, fileNames);
            });
        });

        document.querySelectorAll('.author-link-inline-input').forEach(function (input) {
            input.addEventListener('input', function () {
                const box = input.closest('.author-file-link-box');
                const fileInput = box ? box.querySelector('.author-file-native') : null;

                if (fileInput && input.value.trim() !== '') {
                    fileInput.value = '';
                    updateDocumentLinkPlaceholder(box, []);
                }

                input.classList.toggle('is-invalid', input.value.trim() !== '' && !input.checkValidity());
            });
        });

        document.querySelectorAll('.infographic-input').forEach(function (input) {
            input.addEventListener('change', function () {
                const preview = input.closest('form').querySelector('.selected-preview');
                if (!preview) return;
                preview.innerHTML = '';
                Array.from(input.files || []).forEach(function (file) {
                    if (!file.type.startsWith('image/')) return;
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        const img = document.createElement('img');
                        img.src = event.target.result;
                        img.className = 'infographic-thumb';
                        preview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                });
            });
        });
    });
</script>
@endpush
