            <div class="card table-card {{ $historyCardClass ?? '' }}">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold">Riwayat Pemeriksaan</h6>
                        <small class="text-muted">Riwayat terbaru ditampilkan dalam area scroll.</small>
                    </div>
                </div>

                <div class="card-body">
                    @php
                        $allReviews = $publicationTeam->drafts
                            ->flatMap(fn($draft) => $draft->reviews)
                            ->sortByDesc('created_at')
                            ->values();

                        $revisionNumbers = $allReviews
                            ->where('result', 'revisi')
                            ->groupBy('review_type')
                            ->flatMap(function ($items) {
                                return $items
                                    ->sortBy(fn($review) => optional($review->reviewed_at ?? $review->created_at)->timestamp ?? 0)
                                    ->values()
                                    ->mapWithKeys(fn($review, $index) => [$review->id => $index + 1]);
                            });
                    @endphp

                    <div class="review-history-scroll" style="max-height: 620px; overflow-y: auto; padding-right: 6px;">
                            @forelse($allReviews as $review)
                                @php
                                    $revisionSlides = data_get($review->checklist, 'slides', []);
                                    $revisionDocuments = collect(data_get($review->checklist, 'revision_documents', []));
                                    $reviewTypeLabel = $review->review_type_label;
                                    $revisionNumber = $review->result === 'revisi' ? ($revisionNumbers[$review->id] ?? 1) : null;
                                    $reviewTitle = $review->result === 'revisi'
                                        ? $reviewTypeLabel . ' - Revisi ' . $revisionNumber
                                        : $reviewTypeLabel . ' - ' . $review->result_label;
                                    $draftVersion = data_get($review->checklist, 'draft_version', optional($review->draft)->version);
                                    $modalId = 'revision-modal-' . $review->id;
                                    $hasRevisionDetail = $review->result === 'revisi'
                                        && (count($revisionSlides) > 0 || $review->review_type === 'infografis' || $review->review_type === 'pimpinan');
                                @endphp

                                <div class="review-history-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong>{{ $reviewTitle }}</strong>
                                            @if($draftVersion)
                                                <span class="badge bg-light text-dark border ms-1">Draft V{{ $draftVersion }}</span>
                                            @endif
                                            <br>
                                            <small class="text-muted">
                                                Oleh {{ optional($review->reviewer)->name ?? '-' }}<br>
                                                {{ optional($review->reviewed_at)->format('d-m-Y H:i') }}
                                            </small>
                                        </div>

                                        @if($hasRevisionDetail)
                                            <button type="button" class="btn btn-outline-danger btn-sm revision-detail-btn" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                                <i class="bi bi-eye me-1"></i> Lihat Revisi
                                            </button>
                                        @endif
                                    </div>
                                    <div class="small mt-2 text-muted">{{ $review->notes }}</div>
                                </div>

                                @if($hasRevisionDetail)
                                    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content revision-modal-content">
                                                <div class="modal-header align-items-start">
                                                    <div>
                                                        <h5 class="modal-title fw-bold">Detail {{ $reviewTitle }}</h5>
                                                        @if(in_array($review->review_type, ['konten', 'layout'], true))
                                                            <small class="text-muted">Yang ditampilkan hanya rincian yang dipilih Tidak oleh pemeriksa.</small>
                                                        @elseif($review->review_type === 'infografis')
                                                            <small class="text-muted">Menampilkan catatan revisi serta file hasil pemeriksaan infografis.</small>
                                                        @else
                                                            <small class="text-muted">Menampilkan catatan revisi dari pimpinan.</small>
                                                        @endif
                                                    </div>
                                                    <div class="d-flex align-items-start gap-2 ms-auto">
                                                        @if(in_array($review->review_type, ['konten', 'layout'], true) && count($revisionSlides) > 0)
                                                            <a href="{{ route('employee.tasks.review-revision.pdf', $review->id) }}" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                                                            </a>
                                                        @endif
                                                        <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="revision-final-note mb-3">
                                                        <strong>Catatan keputusan akhir:</strong><br>
                                                        {{ data_get($review->checklist, 'final_notes', $review->notes) }}
                                                    </div>

                                                    @if(in_array($review->review_type, ['konten', 'layout'], true))
                                                        @foreach($revisionSlides as $slide)
                                                            <div class="revision-slide-card">
                                                                <div class="revision-slide-head">
                                                                    <div>
                                                                        <span>Anatomi</span>
                                                                        <h6>{{ data_get($slide, 'anatomy_section', '-') }}</h6>
                                                                        <small class="revision-sub-anatomy">Sub-anatomi: {{ data_get($slide, 'sub_anatomy', '-') }}</small>
                                                                    </div>
                                                                    <small>{{ data_get($slide, 'reviewer_role', 'Pemeriksa') }}: {{ data_get($slide, 'reviewer_name', '-') }}</small>
                                                                </div>

                                                                <div class="revision-failed-list">
                                                                    <strong>Rincian yang perlu direvisi:</strong>
                                                                    <ul>
                                                                        @foreach(data_get($slide, 'failed_items', []) as $failedItem)
                                                                            <li>
                                                                                <span>{{ data_get($failedItem, 'requirement_detail', '-') }}</span>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                </div>

                                                                <div class="revision-note-box">
                                                                    <strong>Catatan slide:</strong><br>
                                                                    {{ data_get($slide, 'notes') ?: 'Tidak ada catatan tambahan.' }}
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    @elseif($review->review_type === 'infografis')
                                                        <div class="revision-slide-card">
                                                            <div class="revision-slide-head">
                                                                <div>
                                                                    <span>File hasil pemeriksaan</span>
                                                                    <h6>Infografis dan Daftar Tabel/Gambar</h6>
                                                                    <small class="revision-sub-anatomy">File diunggah oleh operator infografis sebagai referensi revisi.</small>
                                                                </div>
                                                            </div>

                                                            @if($revisionDocuments->count() > 0)
                                                                <div class="d-flex flex-column gap-2">
                                                                    @foreach($revisionDocuments as $document)
                                                                        <div class="d-flex justify-content-between align-items-center gap-2 border rounded-3 p-2 bg-light">
                                                                            <div class="small">
                                                                                <strong>{{ data_get($document, 'label', '-') }}</strong><br>
                                                                                <span class="text-muted">V{{ data_get($document, 'version', '-') }} • {{ data_get($document, 'file_original_name', '-') }}</span>
                                                                            </div>
                                                                            @if(data_get($document, 'id'))
                                                                                @php
                                                                                    $documentIsLink = (bool) data_get($document, 'is_link', false)
                                                                                        || data_get($document, 'source_type') === 'link'
                                                                                        || \Illuminate\Support\Str::startsWith((string) data_get($document, 'file_original_name', ''), 'Link ');
                                                                                @endphp
                                                                                <a href="{{ route('employee.tasks.download-document', data_get($document, 'id')) }}" class="btn btn-outline-primary btn-sm" target="{{ $documentIsLink ? '_blank' : '_self' }}">
                                                                                    <i class="bi {{ $documentIsLink ? 'bi-box-arrow-up-right' : 'bi-download' }} me-1"></i> {{ $documentIsLink ? 'Buka Link' : 'Download' }}
                                                                                </a>
                                                                            @endif
                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @else
                                                                <small class="text-muted">Tidak ada file hasil pemeriksaan yang diunggah.</small>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @empty
                                <small class="text-muted">Belum ada riwayat pemeriksaan.</small>
                            @endforelse
                    </div>
                </div>
            </div>
