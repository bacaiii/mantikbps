<?php
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
?>

<div class="<?php echo e($cardClass); ?>" <?php if($section !== 'summary'): ?> data-review-workspace="<?php echo e($type); ?>" <?php endif; ?>>
    <?php if($section !== 'details'): ?>
<div class="review-workspace-header">
        <div>
            <span class="review-eyebrow"><?php echo e($typeLabel); ?></span>
            <h5 class="mb-1 fw-bold">Form Koreksi Per Slide Sub-Anatomi</h5>
            <small>
                Setiap slide berisi satu anatomi dan satu sub-anatomi. Pilih Ya/Tidak pada setiap rincian, lalu isi satu catatan slide jika ada rincian yang dipilih Tidak.
            </small>
        </div>
        <div class="review-header-badge">
            <?php echo e($isKetua ? $chiefLabel : 'Anggota Pemeriksa'); ?>

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
            <?php $__empty_1 = true; $__currentLoopData = $examinationDocuments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="review-document-card">
                    <div class="review-document-icon">
                        <i class="bi <?php echo e(match($document->document_type) {
                            'naskah_pdf' => 'bi-file-earmark-pdf',
                            'naskah_zip' => 'bi-file-earmark-zip',
                            'daftar_tabel_gambar' => 'bi-file-earmark-spreadsheet',
                            default => 'bi-file-earmark',
                        }); ?>"></i>
                    </div>
                    <div class="review-document-content">
                        <strong><?php echo e($document->document_type_label); ?></strong>
                        <span>Versi <?php echo e($document->version); ?></span>
                        <small title="<?php echo e($document->file_original_name); ?>"><?php echo e($document->file_original_name); ?></small>
                    </div>
                    <a href="<?php echo e(route('employee.tasks.download-document', $document->id)); ?>" class="btn btn-outline-primary btn-sm" target="<?php echo e($document->is_link ? '_blank' : '_self'); ?>">
                        <i class="bi <?php echo e($document->is_link ? 'bi-box-arrow-up-right' : 'bi-download'); ?> me-1"></i> <?php echo e($document->is_link ? 'Buka Link' : 'Download'); ?>

                    </a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="review-document-empty">
                    Belum ada dokumen yang dapat diperiksa.
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php if($section !== 'summary'): ?>
    <?php if(!$latestDraft): ?>
        <div class="alert alert-warning m-4">
            <strong>Belum ada naskah PDF.</strong><br>
            Pemeriksaan baru dapat dilakukan setelah Tim Penyusun mengunggah naskah PDF dan menekan Submit.
        </div>
    <?php elseif($slideCount < 1): ?>
        <div class="alert alert-warning m-4">
            <strong>Pedoman <?php echo e(strtolower($typeLabel)); ?> belum tersedia.</strong><br>
            Admin perlu menambahkan pedoman pemeriksaan pada menu Kelola Pedoman Pemeriksaan.
        </div>
    <?php else: ?>
        <div class="review-slide-nav">
            <?php $__currentLoopData = $slides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $saved = $savedSlides->get($slide['key']);
                    $savedAnswers = collect(optional($saved)->answers ?? []);
                    $hasRevision = $savedAnswers->contains(fn($item) => ($item['answer'] ?? null) === 'tidak');
                    $slideTotal = $slide['items']->count();
                    $filledCount = $savedAnswers->filter(fn($item) => in_array($item['answer'] ?? null, ['ya', 'tidak'], true))->count();
                ?>
                <button type="button"
                        class="review-slide-tab <?php echo e($loop->first ? 'active' : ''); ?>"
                        data-review-target="<?php echo e($type); ?>-slide-<?php echo e($loop->iteration); ?>">
                    <span><?php echo e($loop->iteration); ?></span>
                    <strong><?php echo e($slide['anatomy_section']); ?></strong>
                    <small><?php echo e($slide['sub_anatomy']); ?></small>
                    <?php if($saved): ?>
                        <em class="<?php echo e($hasRevision ? 'revision' : 'saved'); ?>">
                            <?php echo e($filledCount); ?>/<?php echo e($slideTotal); ?> terisi<?php echo e($hasRevision ? ' · Ada revisi' : ''); ?>

                        </em>
                    <?php else: ?>
                        <em>0/<?php echo e($slideTotal); ?> terisi</em>
                    <?php endif; ?>
                </button>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="review-slide-area">
            <?php $__currentLoopData = $slides; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $slide): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $saved = $savedSlides->get($slide['key']);
                    $answerCollection = collect(optional($saved)->answers ?? []);
                    $answerMap = $answerCollection->keyBy('guideline_id');
                    $savedAt = optional($saved)->saved_at;
                    $updatedByName = optional(optional($saved)->reviewer)->name;
                    $isFirstSlide = $loop->first;
                    $isLastSlide = $loop->last;
                    $slideTotal = $slide['items']->count();
                    $filledCount = $answerCollection->filter(fn($item) => in_array($item['answer'] ?? null, ['ya', 'tidak'], true))->count();
                ?>

                <div id="<?php echo e($type); ?>-slide-<?php echo e($loop->iteration); ?>"
                     class="review-slide <?php echo e($loop->first ? 'active' : ''); ?>"
                     data-review-slide="<?php echo e($type); ?>">
                    <form action="<?php echo e(route('employee.tasks.review-slide.save', [$publicationTeam->id, $type])); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="anatomy_section" value="<?php echo e($slide['anatomy_section']); ?>">
                        <input type="hidden" name="sub_anatomy" value="<?php echo e($slide['sub_anatomy']); ?>">

                        <div class="review-slide-head review-slide-head-clean">
                            <div>
                                <span>Slide <?php echo e($loop->iteration); ?> dari <?php echo e($slideCount); ?></span>
                                <h4><?php echo e($slide['anatomy_section']); ?></h4>
                                <p><?php echo e($slide['sub_anatomy']); ?></p>
                            </div>
                            <div class="review-slide-meta">
                                <div class="review-count-pill">
                                    <i class="bi bi-list-check"></i>
                                    <?php echo e($slideTotal); ?> Rincian
                                </div>
                                <div class="review-progress-pill <?php echo e($filledCount === $slideTotal && $slideTotal > 0 ? 'complete' : ''); ?>">
                                    <?php echo e($filledCount); ?>/<?php echo e($slideTotal); ?> Terisi
                                </div>
                                <?php if($savedAt): ?>
                                    <div class="review-saved-pill">
                                        <i class="bi bi-check2-circle"></i>
                                        Tersimpan <?php echo e($savedAt->format('d-m-Y H:i')); ?>

                                    </div>
                                    <div class="review-updated-by-text">
                                        Diperbarui oleh: <strong><?php echo e($updatedByName ?: 'Pegawai Pemeriksa'); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="review-items-list review-items-slide-list">
                            <div class="review-section-caption">
                                <i class="bi bi-list-check"></i>
                                <strong>Rincian pemeriksaan</strong>
                            </div>

                            <?php $__currentLoopData = $slide['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guideline): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $answer = data_get($answerMap->get($guideline->id), 'answer');
                                    $radioName = 'answers[' . $guideline->id . ']';
                                    $radioId = $type . '-' . $loop->parent->iteration . '-' . $guideline->id;
                                ?>
                                <div class="review-check-item review-check-item-compact">
                                    <div class="review-check-text">
                                        <strong>Rincian <?php echo e($loop->iteration); ?></strong>
                                        <p><?php echo nl2br(e($guideline->requirement_detail)); ?></p>
                                    </div>

                                    <div class="review-yesno-selector">
                                        <input type="radio"
                                               class="btn-check"
                                               name="<?php echo e($radioName); ?>"
                                               value="ya"
                                               id="<?php echo e($radioId); ?>-ya"
                                               autocomplete="off"
                                               <?php echo e($answer === 'ya' ? 'checked' : ''); ?>>
                                        <label class="btn review-choice yes" for="<?php echo e($radioId); ?>-ya">
                                            Ya
                                        </label>

                                        <input type="radio"
                                               class="btn-check"
                                               name="<?php echo e($radioName); ?>"
                                               value="tidak"
                                               id="<?php echo e($radioId); ?>-tidak"
                                               autocomplete="off"
                                               <?php echo e($answer === 'tidak' ? 'checked' : ''); ?>>
                                        <label class="btn review-choice no" for="<?php echo e($radioId); ?>-tidak">
                                            Tidak
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>

                        <div class="review-note-box review-note-box-bottom">
                            <label class="form-label fw-semibold">Catatan Pemeriksaan</label>
                            <textarea name="notes"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Tulis satu catatan untuk slide <?php echo e($slide['sub_anatomy']); ?>. Catatan ini akan muncul di menu penyusun jika ada rincian yang dipilih Tidak."><?php echo e(old('notes', optional($saved)->notes)); ?></textarea>
                            <small>Catatan hanya satu box per slide. Jika semua rincian dipilih Ya, catatan boleh dikosongkan.</small>
                        </div>

                        <div class="review-slide-actions review-slide-actions-clean">
                            <button type="button" class="btn btn-light review-prev-btn" <?php echo e($isFirstSlide ? 'disabled' : ''); ?>>
                                <i class="bi bi-chevron-left me-1"></i> Back
                            </button>

                            <button class="btn btn-primary review-save-btn">
                                <i class="bi bi-save2 me-1"></i> Simpan Sementara
                            </button>

                            <?php if($isLastSlide): ?>
                                <button type="button" class="btn btn-light review-next-btn" disabled>
                                    Slide Terakhir
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn btn-light review-next-btn">
                                    Next <i class="bi bi-chevron-right ms-1"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="review-final-panel">
            <?php if($isKetua): ?>
                <form action="<?php echo e($reviewRoute); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="review-final-head">
                        <div>
                            <h6>Keputusan Akhir <?php echo e($typeLabel); ?></h6>
                            <small>Bagian ini hanya muncul untuk ketua pemeriksa setelah semua slide dicek.</small>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Keputusan Akhir</label>
                            <select name="result" class="form-select" required>
                                <option value="">-- Pilih Keputusan --</option>
                                <option value="disetujui">Disetujui / Lanjut ke <?php echo e($nextLabel); ?></option>
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
            <?php else: ?>
                <div class="review-member-info">
                    <i class="bi bi-info-circle"></i>
                    <div>
                        <strong>Anda sebagai anggota pemeriksa.</strong><br>
                        Silakan isi dan simpan pemeriksaan per slide. Keputusan akhir hanya dilakukan oleh ketua pemeriksa.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>
</div>
<?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/employee/tasks/_review_workspace.blade.php ENDPATH**/ ?>