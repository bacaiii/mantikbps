<div class="employee-document-version-card">
    <div class="employee-document-version-head">
        <div>
            <strong>Form SPRP</strong>
            <small>Surat Pernyataan Rilis Publikasi</small>
        </div>
        <div class="text-end">
            <?php if($sprp): ?>
                <span class="badge bg-primary-subtle text-primary">Ada</span>
            <?php else: ?>
                <span class="badge bg-light text-muted border">Tidak Ada</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="version-slider-shell employee-version-slider-shell single-version">
        <div class="version-slide-stage">
            <div class="version-slide is-active">
                <div class="employee-document-version-body">
                    <div class="employee-document-file-info">
                        <?php if($sprp): ?>
                            <span>SPRP</span>
                            <strong>Form SPRP telah diisi</strong>
                            <small>
                                Oleh <?php echo e(optional($sprp->submittedBy)->name ?? '-'); ?><br>
                                <?php echo e(optional($sprp->submitted_at)->format('d-m-Y H:i') ?? '-'); ?>

                            </small>
                        <?php else: ?>
                            <span>SPRP</span>
                            <strong>Form SPRP belum diisi</strong>
                            <small>Belum tersedia untuk publikasi ini.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($sprp): ?>
                    <div class="employee-document-version-actions">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo e($modalId); ?>">
                            <i class="bi bi-eye me-1"></i> Lihat
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/shared/_sprp_document_box.blade.php ENDPATH**/ ?>