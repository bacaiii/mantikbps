            <div class="card table-card <?php echo e($historyCardClass ?? ''); ?>">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center gap-2">
                    <div>
                        <h6 class="mb-0 fw-bold">Riwayat Pemeriksaan</h6>
                        <small class="text-muted">Riwayat terbaru ditampilkan dalam area scroll.</small>
                    </div>
                </div>

                <div class="card-body">
                    <?php
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
                    ?>

                    <div class="review-history-scroll" style="max-height: 620px; overflow-y: auto; padding-right: 6px;">
                            <?php $__empty_1 = true; $__currentLoopData = $allReviews; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $review): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <?php
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
                                ?>

                                <div class="review-history-item">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <strong><?php echo e($reviewTitle); ?></strong>
                                            <?php if($draftVersion): ?>
                                                <span class="badge bg-light text-dark border ms-1">Draft V<?php echo e($draftVersion); ?></span>
                                            <?php endif; ?>
                                            <br>
                                            <small class="text-muted">
                                                Oleh <?php echo e(optional($review->reviewer)->name ?? '-'); ?><br>
                                                <?php echo e(optional($review->reviewed_at)->format('d-m-Y H:i')); ?>

                                            </small>
                                        </div>

                                        <?php if($hasRevisionDetail): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm revision-detail-btn" data-bs-toggle="modal" data-bs-target="#<?php echo e($modalId); ?>">
                                                <i class="bi bi-eye me-1"></i> Lihat Revisi
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <div class="small mt-2 text-muted"><?php echo e($review->notes); ?></div>
                                </div>

                                <?php if($hasRevisionDetail): ?>
                                    <div class="modal fade" id="<?php echo e($modalId); ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                                            <div class="modal-content revision-modal-content">
                                                <div class="modal-header align-items-start">
                                                    <div>
                                                        <h5 class="modal-title fw-bold">Detail <?php echo e($reviewTitle); ?></h5>
                                                        <?php if(in_array($review->review_type, ['konten', 'layout'], true)): ?>
                                                            <small class="text-muted">Yang ditampilkan hanya rincian yang dipilih Tidak oleh pemeriksa.</small>
                                                        <?php elseif($review->review_type === 'infografis'): ?>
                                                            <small class="text-muted">Menampilkan catatan revisi serta file hasil pemeriksaan infografis.</small>
                                                        <?php else: ?>
                                                            <small class="text-muted">Menampilkan catatan revisi dari pimpinan.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex align-items-start gap-2 ms-auto">
                                                        <?php if(in_array($review->review_type, ['konten', 'layout'], true) && count($revisionSlides) > 0): ?>
                                                            <a href="<?php echo e(route('employee.tasks.review-revision.pdf', $review->id)); ?>" class="btn btn-outline-danger btn-sm">
                                                                <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn-close mt-1" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="revision-final-note mb-3">
                                                        <strong>Catatan keputusan akhir:</strong><br>
                                                        <?php echo e(data_get($review->checklist, 'final_notes', $review->notes)); ?>

                                                    </div>

                                                    <?php if(in_array($review->review_type, ['konten', 'layout'], true)): ?>
                                                        <?php $__currentLoopData = $revisionSlides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                            <div class="revision-slide-card">
                                                                <div class="revision-slide-head">
                                                                    <div>
                                                                        <span>Anatomi</span>
                                                                        <h6><?php echo e(data_get($slide, 'anatomy_section', '-')); ?></h6>
                                                                        <small class="revision-sub-anatomy">Sub-anatomi: <?php echo e(data_get($slide, 'sub_anatomy', '-')); ?></small>
                                                                    </div>
                                                                    <small><?php echo e(data_get($slide, 'reviewer_role', 'Pemeriksa')); ?>: <?php echo e(data_get($slide, 'reviewer_name', '-')); ?></small>
                                                                </div>

                                                                <div class="revision-failed-list">
                                                                    <strong>Rincian yang perlu direvisi:</strong>
                                                                    <ul>
                                                                        <?php $__currentLoopData = data_get($slide, 'failed_items', []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $failedItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                            <li>
                                                                                <span><?php echo e(data_get($failedItem, 'requirement_detail', '-')); ?></span>
                                                                            </li>
                                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                    </ul>
                                                                </div>

                                                                <div class="revision-note-box">
                                                                    <strong>Catatan slide:</strong><br>
                                                                    <?php echo e(data_get($slide, 'notes') ?: 'Tidak ada catatan tambahan.'); ?>

                                                                </div>
                                                            </div>
                                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                    <?php elseif($review->review_type === 'infografis'): ?>
                                                        <div class="revision-slide-card">
                                                            <div class="revision-slide-head">
                                                                <div>
                                                                    <span>File hasil pemeriksaan</span>
                                                                    <h6>Infografis dan Daftar Tabel/Gambar</h6>
                                                                    <small class="revision-sub-anatomy">File diunggah oleh operator infografis sebagai referensi revisi.</small>
                                                                </div>
                                                            </div>

                                                            <?php if($revisionDocuments->count() > 0): ?>
                                                                <div class="d-flex flex-column gap-2">
                                                                    <?php $__currentLoopData = $revisionDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                                        <div class="d-flex justify-content-between align-items-center gap-2 border rounded-3 p-2 bg-light">
                                                                            <div class="small">
                                                                                <strong><?php echo e(data_get($document, 'label', '-')); ?></strong><br>
                                                                                <span class="text-muted">V<?php echo e(data_get($document, 'version', '-')); ?> • <?php echo e(data_get($document, 'file_original_name', '-')); ?></span>
                                                                            </div>
                                                                            <?php if(data_get($document, 'id')): ?>
                                                                                <?php
                                                                                    $documentIsLink = (bool) data_get($document, 'is_link', false)
                                                                                        || data_get($document, 'source_type') === 'link'
                                                                                        || \Illuminate\Support\Str::startsWith((string) data_get($document, 'file_original_name', ''), 'Link ');
                                                                                ?>
                                                                                <a href="<?php echo e(route('employee.tasks.download-document', data_get($document, 'id'))); ?>" class="btn btn-outline-primary btn-sm" target="<?php echo e($documentIsLink ? '_blank' : '_self'); ?>">
                                                                                    <i class="bi <?php echo e($documentIsLink ? 'bi-box-arrow-up-right' : 'bi-download'); ?> me-1"></i> <?php echo e($documentIsLink ? 'Buka Link' : 'Download'); ?>

                                                                                </a>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <small class="text-muted">Tidak ada file hasil pemeriksaan yang diunggah.</small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <small class="text-muted">Belum ada riwayat pemeriksaan.</small>
                            <?php endif; ?>
                    </div>
                </div>
            </div>
<?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/employee/tasks/_review_history_card.blade.php ENDPATH**/ ?>