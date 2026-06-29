<div class="workflow-card infographic-review-card mb-4">
    <div class="workflow-card-head">
        <div>
            <span class="workflow-eyebrow"><i class="bi bi-images me-1"></i> Operator Infografis</span>
            <h5 class="mb-1 fw-bold">Pemeriksaan Infografis dan Daftar Tabel/Gambar</h5>
            <small class="text-muted">Periksa file Excel daftar tabel & gambar serta file infografis yang diunggah Tim Penyusun.</small>
        </div>
        <span class="status-chip info">Aktif</span>
    </div>

    @php
        $infographics = $documentsByType->get('infografis', collect())->sortByDesc('version')->values();
        $tableLists = $documentsByType->get('daftar_tabel_gambar', collect())->sortByDesc('version')->values();
        $pdfDocuments = $documentsByType->get('naskah_pdf', collect())->sortByDesc('version')->values();
        $zipDocuments = $documentsByType->get('naskah_zip', collect())->sortByDesc('version')->values();

        $infographicDocumentCards = [
            [
                'label' => 'Naskah PDF',
                'subtitle' => 'Dokumen untuk dilihat',
                'icon' => 'bi-file-earmark-pdf',
                'items' => $pdfDocuments,
            ],
            [
                'label' => 'Naskah RAR/ZIP',
                'subtitle' => 'Dokumen untuk dilihat',
                'icon' => 'bi-file-earmark-zip',
                'items' => $zipDocuments,
            ],
            [
                'label' => 'Daftar Tabel & Gambar',
                'subtitle' => 'File Excel',
                'icon' => 'bi-file-earmark-spreadsheet',
                'items' => $tableLists,
                'optional_for_kabkota' => true,
            ],
            [
                'label' => 'Infografis',
                'subtitle' => 'File gambar',
                'icon' => 'bi-image',
                'items' => $infographics,
                'optional_for_kabkota' => true,
            ],
        ];
    @endphp

    <div class="row g-3 mt-2">
        @foreach($infographicDocumentCards as $card)
            @php
                $items = collect($card['items'])->values();
                $totalVersions = $items->count();
                $isOptionalForKabKota = !$isProvinsiTenant && ($card['optional_for_kabkota'] ?? false);
            @endphp
            <div class="col-md-6">
                <div class="border rounded-3 p-3 h-100 bg-light" @if($totalVersions) data-version-slider @endif>
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div class="d-flex align-items-start gap-2">
                            <span class="badge bg-white text-primary border rounded-3 p-2">
                                <i class="bi {{ $card['icon'] }}"></i>
                            </span>
                            <div>
                                <small class="text-muted d-block">{{ $card['subtitle'] }}</small>
                                <strong>{{ $card['label'] }}</strong>
                            </div>
                        </div>
                        @if($totalVersions > 0)
                            <div class="text-end">
                                <span class="badge bg-primary-subtle text-primary">Ada</span>
                                @if($totalVersions > 1)
                                    <div class="mt-1">
                                        <span class="badge bg-white text-primary border" data-version-counter>1/{{ $totalVersions }}</span>
                                    </div>
                                @endif
                            </div>
                        @elseif($isOptionalForKabKota)
                            <span class="badge bg-secondary-subtle text-secondary">Tidak Diperlukan</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Belum</span>
                        @endif
                    </div>

                    @if($totalVersions > 0)
                        <div class="version-slider-shell employee-version-slider-shell {{ $totalVersions <= 1 ? 'single-version' : '' }} mt-2">
                            @if($totalVersions > 1)
                                <button type="button" class="version-nav-btn" data-version-prev aria-label="Versi sebelumnya">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                            @endif

                            <div class="version-slide-stage">
                                @foreach($items as $document)
                                    <div class="version-slide {{ $loop->first ? 'is-active' : '' }}" data-version-slide>
                                        <div class="employee-document-file-info">
                                            <span>Versi {{ $document->version }}</span>
                                            <strong title="{{ $document->file_original_name }}">{{ $document->file_original_name }}</strong>
                                            <small>
                                                Oleh {{ optional($document->uploader)->name ?? '-' }}<br>
                                                {{ optional($document->uploaded_at)->format('d-m-Y H:i') }}
                                            </small>
                                        </div>

                                        <div class="employee-document-version-actions">
                                            <a href="{{ route('employee.tasks.download-document', $document->id) }}" class="btn btn-outline-primary btn-sm" target="{{ $document->is_link ? '_blank' : '_self' }}">
                                                <i class="bi {{ $document->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $document->is_link ? 'Buka Link' : 'Download' }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if($totalVersions > 1)
                                <button type="button" class="version-nav-btn" data-version-next aria-label="Versi berikutnya">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            @endif
                        </div>
                    @else
                        <small class="text-muted">
                            {{ $isOptionalForKabKota ? 'Tidak diperlukan untuk publikasi kabupaten/kota.' : 'File belum tersedia dari Tim Penyusun.' }}
                        </small>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <form action="{{ route('employee.tasks.review-infographic', $publicationTeam->id) }}" method="POST" enctype="multipart/form-data" class="mt-3">
        @csrf
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label fw-semibold">Keputusan Pemeriksaan</label>
                <select name="result" class="form-select" required>
                    <option value="">-- Pilih Keputusan --</option>
                    <option value="disetujui" {{ old('result') === 'disetujui' ? 'selected' : '' }}>Disetujui, lanjut ke Persetujuan Pimpinan</option>
                    <option value="revisi" {{ old('result') === 'revisi' ? 'selected' : '' }}>Revisi, kembalikan ke Tim Penyusun</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label fw-semibold">Catatan Operator Infografis</label>
                <textarea name="final_notes" class="form-control" rows="3" placeholder="Tulis ringkasan hasil pemeriksaan infografis dan file daftar tabel/gambar..." required>{{ old('final_notes') }}</textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">File/Link Hasil Pemeriksaan Daftar Tabel & Gambar</label>
                <div class="author-upload-input-line infographic-review-input-line">
                    <div class="author-file-link-box">
                        <label class="author-file-button mb-0">
                            Pilih File
                            <input type="file" name="review_table_file" class="author-file-native" accept=".xls,.xlsx,.csv">
                        </label>

                        <input type="url"
                               name="review_table_url"
                               class="author-link-inline-input"
                               placeholder="atau tempel link dokumen eksternal"
                               inputmode="url">
                    </div>
                </div>
                <small class="text-muted">Opsional. Unggah Excel/catatan daftar tabel & gambar atau tempel link sebagai referensi revisi.</small>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold">File/Link Hasil Pemeriksaan Infografis</label>
                <div class="author-upload-input-line infographic-review-input-line">
                    <div class="author-file-link-box">
                        <label class="author-file-button mb-0">
                            Pilih File
                            <input type="file" name="review_infographic_file" class="author-file-native" accept=".jpg,.jpeg,.png">
                        </label>

                        <input type="url"
                               name="review_infographic_url"
                               class="author-link-inline-input"
                               placeholder="atau tempel link dokumen eksternal"
                               inputmode="url">
                    </div>
                </div>
                <small class="text-muted">Opsional. Unggah gambar coretan/catatan infografis atau tempel link sebagai referensi revisi.</small>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3">
            <button class="btn btn-primary">
                <i class="bi bi-check2-circle me-1"></i> Simpan Keputusan Infografis
            </button>
        </div>
    </form>
</div>
