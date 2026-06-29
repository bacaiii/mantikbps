@php
    $typeLabel = $type === 'konten' ? 'Pemeriksaan Konten' : 'Pemeriksaan Layout';
    $chiefLabel = $type === 'konten' ? 'Ketua Pemeriksa Konten' : 'Ketua Pemeriksa Layout';
    $nextLabel = $type === 'konten' ? 'Pemeriksaan Layout' : 'Pemeriksaan Infografis';
    $reviewRoute = $type === 'konten'
        ? route('employee.tasks.review-content', $publicationTeam->id)
        : route('employee.tasks.review-layout', $publicationTeam->id);
    $slideCount = $slides->count();
    $section = $section ?? 'full';
    $cardClass = 'review-workspace-card mb-4';
    if ($section === 'summary') {
        $cardClass .= ' review-workspace-summary-card';
    }
    if ($section === 'details') {
        $cardClass .= ' review-workspace-details-card';
    }
@endphp

<div class="{{ $cardClass }}" @if($section !== 'summary') data-review-workspace="{{ $type }}" @endif>
    @if($section !== 'details')
<div class="review-workspace-header">
        <div>
            <span class="review-eyebrow">{{ $typeLabel }}</span>
            <h5 class="mb-1 fw-bold">Form Koreksi Per Slide Sub-Anatomi</h5>
            <small>
                Setiap slide berisi satu anatomi dan satu sub-anatomi. Pilih Ya/Tidak pada setiap rincian, lalu isi satu catatan slide jika ada rincian yang dipilih Tidak.
            </small>
        </div>
        <div class="review-header-badge">
            {{ $isKetua ? $chiefLabel : 'Anggota Pemeriksa' }}
        </div>
    </div>

    <div class="review-documents-panel">
        <div class="review-documents-title">
            <i class="bi bi-folder2-open"></i>
            <div>
                <strong>Dokumen yang Diperiksa</strong>
                <small>Fokus pemeriksaan pada naskah PDF, file sumber RAR/ZIP, serta daftar tabel & gambar jika tersedia.</small>
            </div>
        </div>

        <div class="review-documents-list review-document-card-grid">
            @forelse($examinationDocuments as $document)
                <div class="review-document-card">
                    <div class="review-document-icon">
                        <i class="bi {{ match($document->document_type) {
                            'naskah_pdf' => 'bi-file-earmark-pdf',
                            'naskah_zip' => 'bi-file-earmark-zip',
                            'daftar_tabel_gambar' => 'bi-file-earmark-spreadsheet',
                            default => 'bi-file-earmark',
                        } }}"></i>
                    </div>
                    <div class="review-document-content">
                        <strong>{{ $document->document_type_label }}</strong>
                        <span>Versi {{ $document->version }}</span>
                        <small title="{{ $document->file_original_name }}">{{ $document->file_original_name }}</small>
                    </div>
                    <a href="{{ route('employee.tasks.download-document', $document->id) }}" class="btn btn-outline-primary btn-sm" target="{{ $document->is_link ? '_blank' : '_self' }}">
                        <i class="bi {{ $document->is_link ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $document->is_link ? 'Buka Link' : 'Download' }}
                    </a>
                </div>
            @empty
                <div class="review-document-empty">
                    Belum ada dokumen yang dapat diperiksa.
                </div>
            @endforelse
        </div>
    </div>

@endif

@if($section !== 'summary')
    @if(!$latestDraft)
        <div class="alert alert-warning m-4">
            <strong>Belum ada naskah PDF.</strong><br>
            Pemeriksaan baru dapat dilakukan setelah Tim Penyusun mengunggah naskah PDF dan menekan Submit.
        </div>
    @elseif($slideCount < 1)
        <div class="alert alert-warning m-4">
            <strong>Pedoman {{ strtolower($typeLabel) }} belum tersedia.</strong><br>
            Admin perlu menambahkan pedoman pemeriksaan pada menu Kelola Pedoman Pemeriksaan.
        </div>
    @else
        <div class="review-slide-nav">
            @foreach($slides as $slide)
                @php
                    $saved = $savedSlides->get($slide['key']);
                    $savedAnswers = collect(optional($saved)->answers ?? []);
                    $hasRevision = $savedAnswers->contains(fn($item) => ($item['answer'] ?? null) === 'tidak');
                    $slideTotal = $slide['items']->count();
                    $filledCount = $savedAnswers->filter(fn($item) => in_array($item['answer'] ?? null, ['ya', 'tidak'], true))->count();
                @endphp
                <button type="button"
                        class="review-slide-tab {{ $loop->first ? 'active' : '' }}"
                        data-review-target="{{ $type }}-slide-{{ $loop->iteration }}">
                    <span>{{ $loop->iteration }}</span>
                    <strong>{{ $slide['anatomy_section'] }}</strong>
                    <small>{{ $slide['sub_anatomy'] }}</small>
                    @if($saved)
                        <em class="{{ $hasRevision ? 'revision' : 'saved' }}">
                            {{ $filledCount }}/{{ $slideTotal }} terisi{{ $hasRevision ? ' · Ada revisi' : '' }}
                        </em>
                    @else
                        <em>0/{{ $slideTotal }} terisi</em>
                    @endif
                </button>
            @endforeach
        </div>

        <div class="review-slide-area">
            @foreach($slides as $slide)
                @php
                    $saved = $savedSlides->get($slide['key']);
                    $answerCollection = collect(optional($saved)->answers ?? []);
                    $answerMap = $answerCollection->keyBy('guideline_id');
                    $savedAt = optional($saved)->saved_at;
                    $updatedByName = optional(optional($saved)->reviewer)->name;
                    $isFirstSlide = $loop->first;
                    $isLastSlide = $loop->last;
                    $slideTotal = $slide['items']->count();
                    $filledCount = $answerCollection->filter(fn($item) => in_array($item['answer'] ?? null, ['ya', 'tidak'], true))->count();
                @endphp

                <div id="{{ $type }}-slide-{{ $loop->iteration }}"
                     class="review-slide {{ $loop->first ? 'active' : '' }}"
                     data-review-slide="{{ $type }}">
                    <form action="{{ route('employee.tasks.review-slide.save', [$publicationTeam->id, $type]) }}" method="POST">
                        @csrf
                        <input type="hidden" name="anatomy_section" value="{{ $slide['anatomy_section'] }}">
                        <input type="hidden" name="sub_anatomy" value="{{ $slide['sub_anatomy'] }}">

                        <div class="review-slide-head review-slide-head-clean">
                            <div>
                                <span>Slide {{ $loop->iteration }} dari {{ $slideCount }}</span>
                                <h4>{{ $slide['anatomy_section'] }}</h4>
                                <p>{{ $slide['sub_anatomy'] }}</p>
                            </div>
                            <div class="review-slide-meta">
                                <div class="review-count-pill">
                                    <i class="bi bi-list-check"></i>
                                    {{ $slideTotal }} Rincian
                                </div>
                                <div class="review-progress-pill {{ $filledCount === $slideTotal && $slideTotal > 0 ? 'complete' : '' }}">
                                    {{ $filledCount }}/{{ $slideTotal }} Terisi
                                </div>
                                @if($savedAt)
                                    <div class="review-saved-pill">
                                        <i class="bi bi-check2-circle"></i>
                                        Tersimpan {{ $savedAt->format('d-m-Y H:i') }}
                                    </div>
                                    <div class="review-updated-by-text">
                                        Diperbarui oleh: <strong>{{ $updatedByName ?: 'Pegawai Pemeriksa' }}</strong>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="review-items-list review-items-slide-list">
                            <div class="review-section-caption">
                                <i class="bi bi-list-check"></i>
                                <strong>Rincian pemeriksaan</strong>
                            </div>

                            @foreach($slide['items'] as $guideline)
                                @php
                                    $answer = data_get($answerMap->get($guideline->id), 'answer');
                                    $radioName = 'answers[' . $guideline->id . ']';
                                    $radioId = $type . '-' . $loop->parent->iteration . '-' . $guideline->id;
                                @endphp
                                <div class="review-check-item review-check-item-compact">
                                    <div class="review-check-text">
                                        <strong>Rincian {{ $loop->iteration }}</strong>
                                        <p>{!! nl2br(e($guideline->requirement_detail)) !!}</p>
                                    </div>

                                    <div class="review-yesno-selector">
                                        <input type="radio"
                                               class="btn-check"
                                               name="{{ $radioName }}"
                                               value="ya"
                                               id="{{ $radioId }}-ya"
                                               autocomplete="off"
                                               {{ $answer === 'ya' ? 'checked' : '' }}>
                                        <label class="btn review-choice yes" for="{{ $radioId }}-ya">
                                            Ya
                                        </label>

                                        <input type="radio"
                                               class="btn-check"
                                               name="{{ $radioName }}"
                                               value="tidak"
                                               id="{{ $radioId }}-tidak"
                                               autocomplete="off"
                                               {{ $answer === 'tidak' ? 'checked' : '' }}>
                                        <label class="btn review-choice no" for="{{ $radioId }}-tidak">
                                            Tidak
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="review-note-box review-note-box-bottom">
                            <label class="form-label fw-semibold">Catatan Pemeriksaan</label>
                            <textarea name="notes"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Tulis satu catatan untuk slide {{ $slide['sub_anatomy'] }}. Catatan ini akan muncul di menu penyusun jika ada rincian yang dipilih Tidak.">{{ old('notes', optional($saved)->notes) }}</textarea>
                            <small>Catatan hanya satu box per slide. Jika semua rincian dipilih Ya, catatan boleh dikosongkan.</small>
                        </div>

                        <div class="review-slide-actions review-slide-actions-clean">
                            <button type="button" class="btn btn-light review-prev-btn" {{ $isFirstSlide ? 'disabled' : '' }}>
                                <i class="bi bi-chevron-left me-1"></i> Back
                            </button>

                            <button class="btn btn-primary review-save-btn">
                                <i class="bi bi-save2 me-1"></i> Simpan Sementara
                            </button>

                            @if($isLastSlide)
                                <button type="button" class="btn btn-light review-next-btn" disabled>
                                    Slide Terakhir
                                </button>
                            @else
                                <button type="button" class="btn btn-light review-next-btn">
                                    Next <i class="bi bi-chevron-right ms-1"></i>
                                </button>
                            @endif
                        </div>
                    </form>
                </div>
            @endforeach
        </div>

        <div class="review-final-panel">
            @if($isKetua)
                <form action="{{ $reviewRoute }}" method="POST">
                    @csrf
                    <div class="review-final-head">
                        <div>
                            <h6>Keputusan Akhir {{ $typeLabel }}</h6>
                            <small>Bagian ini hanya muncul untuk ketua pemeriksa setelah semua slide dicek.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Keputusan Akhir</label>
                            <select name="result" class="form-select" required>
                                <option value="">-- Pilih Keputusan --</option>
                                <option value="disetujui">Disetujui / Lanjut ke {{ $nextLabel }}</option>
                                <option value="revisi">Revisi / Kembalikan ke Tim Penyusun</option>
                            </select>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Catatan Keputusan Akhir</label>
                            <textarea name="final_notes" class="form-control" rows="3" required placeholder="Tulis ringkasan keputusan akhir pemeriksaan."></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Simpan Keputusan Akhir
                        </button>
                    </div>
                </form>
            @else
                <div class="review-member-info">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <strong>Anda sebagai anggota pemeriksa.</strong><br>
                        Silakan isi dan simpan pemeriksaan per slide. Keputusan akhir hanya dilakukan oleh ketua pemeriksa.
                    </div>
                </div>
            @endif
        </div>
    @endif
@endif
</div>
