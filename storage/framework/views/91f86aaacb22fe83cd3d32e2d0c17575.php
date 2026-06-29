<?php $__env->startSection('title', 'Publikasi Siap Rilis'); ?>

<?php $__env->startSection('content'); ?>
    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold">Publikasi Selesai Dibungkus / Siap Rilis</h5>
                <small class="text-muted">Daftar publikasi yang sudah selesai dikelola dan siap rilis.</small>
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Tahun</label>
                    <select name="tahun" class="form-select">
                        <?php $__currentLoopData = $yearOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($year); ?>" <?php echo e((int) $selectedYear === (int) $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Bulan</label>
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        <?php $__currentLoopData = $monthOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthNumber => $monthName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($monthNumber); ?>" <?php echo e((string) $selectedMonth === (string) $monthNumber ? 'selected' : ''); ?>><?php echo e($monthName); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Cari Publikasi</label>
                    <input type="text" name="q" class="form-control" value="<?php echo e(request('q')); ?>" placeholder="Ketik nama publikasi...">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Kategori</label>
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="ARC" <?php echo e(request('kategori') === 'ARC' ? 'selected' : ''); ?>>ARC</option>
                        <option value="Non-ARC" <?php echo e(request('kategori') === 'Non-ARC' ? 'selected' : ''); ?>>Non-ARC</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="accordion knowledge-drawer-list" id="leaderReadyReleaseAccordion">
        <?php $__empty_1 = true; $__currentLoopData = $publications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $publication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $collapseId = 'readyReleaseDrawer' . $publication->id;
                $modalId = 'readyReleaseDocumentModal' . $publication->id;
                $sprpModalId = 'readyReleaseSprpModal' . $publication->id;
                $sprp = $publication->sprp;
                $documentsByType = $publication->documents
                    ->sortByDesc('version')
                    ->groupBy('document_type');

                $materialTypes = [
                    'naskah_pdf' => ['icon' => 'bi-file-earmark-pdf', 'label' => 'Naskah PDF'],
                    'naskah_zip' => ['icon' => 'bi-file-earmark-zip', 'label' => 'File Naskah ZIP/RAR'],
                    'infografis' => ['icon' => 'bi-images', 'label' => 'Infografis'],
                    'daftar_tabel_gambar' => ['icon' => 'bi-file-earmark-spreadsheet', 'label' => 'Daftar Tabel & Gambar'],
                    'surat_persetujuan_rilis' => ['icon' => 'bi-file-earmark-check', 'label' => 'Surat Persetujuan Rilis'],
                ];

                $latestDocuments = collect($materialTypes)->mapWithKeys(function ($meta, $type) use ($documentsByType) {
                    return [$type => ($documentsByType->get($type) ?? collect())->sortByDesc('version')->first()];
                });
            ?>

            <div class="accordion-item border-0 bg-transparent mb-3">
                <h2 class="accordion-header" id="heading<?php echo e($collapseId); ?>">
                    <button class="accordion-button collapsed shadow-sm rounded-4 py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($collapseId); ?>" aria-expanded="false" aria-controls="<?php echo e($collapseId); ?>">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary me-3" style="width: 38px; height: 38px; flex: 0 0 38px;">
                            <i class="bi bi-box-seam"></i>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-bold text-dark"><?php echo e($publication->nama_publikasi); ?></span>
                            <small class="text-muted">
                                <?php echo e($publication->kategori); ?> • <?php echo e($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-'); ?>

                            </small>
                        </span>
                    </button>
                </h2>

                <div id="<?php echo e($collapseId); ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo e($collapseId); ?>" data-bs-parent="#leaderReadyReleaseAccordion">
                    <div class="accordion-body bg-white border rounded-4 mt-2 shadow-sm">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <strong>Estimasi Nomor Publikasi</strong><br>
                                <span class="text-muted"><?php echo e($publication->estimasi_nomor_publikasi ?: '-'); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Kategori</strong><br>
                                <span class="badge <?php echo e($publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary'); ?>"><?php echo e($publication->kategori); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Periode</strong><br>
                                <span class="text-muted"><?php echo e($publication->periode ?? '-'); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Akurasi Publikasi</strong><br>
                                <span class="text-muted"><?php echo e($publication->akurasi_publikasi ?? '-'); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Jadwal Rilis</strong><br>
                                <span class="text-muted"><?php echo e($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('j F Y') : '-'); ?></span>
                            </div>
                            <div class="col-md-4">
                                <strong>Wilayah</strong><br>
                                <span class="text-muted"><?php echo e($publication->wilayah ?? '-'); ?></span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 flex-wrap">
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#<?php echo e($modalId); ?>">
                                <i class="bi bi-folder2-open me-1"></i> Lihat Dokumen
                            </button>
                            <a href="<?php echo e(route('tenant.ready-release.report.pdf', $publication->id)); ?>" class="btn btn-outline-dark btn-sm rounded-pill px-3">
                                <i class="bi bi-file-earmark-pdf me-1"></i> Download Rekap PDF
                            </a>
                            <a href="<?php echo e(route('tenant.ready-release.download-package', $publication->id)); ?>" class="btn btn-success btn-sm rounded-pill px-3 js-package-download" data-loading-title="Menyiapkan Paket Rilis" data-loading-message="Sistem sedang membungkus dokumen final dan mengambil file asli dari link eksternal yang dapat diakses.">
                                <i class="bi bi-file-earmark-zip me-1"></i> Download Paket Rilis
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="<?php echo e($modalId); ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <div>
                                <h5 class="modal-title fw-bold">Dokumen Publikasi</h5>
                                <small class="text-muted"><?php echo e($publication->nama_publikasi); ?></small>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex justify-content-between align-items-center gap-3 border rounded-3 p-3 bg-light">
                                    <div class="d-flex align-items-start gap-3 min-w-0">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 34px; height: 34px; flex: 0 0 34px;">
                                            <i class="bi bi-ui-checks"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <strong>Form SPRP</strong><br>
                                            <?php if($sprp): ?>
                                                <small class="text-muted d-block">Diisi <?php echo e(optional($sprp->submitted_at)->format('d-m-Y H:i') ?? '-'); ?></small>
                                            <?php else: ?>
                                                <small class="text-muted">Belum ada data SPRP</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <?php if($sprp): ?>
                                        <div class="d-flex gap-2 flex-wrap justify-content-end">
                                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#<?php echo e($sprpModalId); ?>">
                                                <i class="bi bi-eye me-1"></i> Lihat
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php $__currentLoopData = $materialTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $latestDocument = $latestDocuments->get($type);
                                    ?>

                                    <div class="d-flex justify-content-between align-items-center gap-3 border rounded-3 p-3 bg-light">
                                        <div class="d-flex align-items-start gap-3 min-w-0">
                                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 34px; height: 34px; flex: 0 0 34px;">
                                                <i class="bi <?php echo e($meta['icon']); ?>"></i>
                                            </span>
                                            <div class="min-w-0">
                                                <strong><?php echo e($meta['label']); ?></strong><br>
                                                <?php if($latestDocument): ?>
                                                    <small class="text-muted d-block text-truncate" title="<?php echo e($latestDocument->file_original_name); ?>">
                                                        V<?php echo e($latestDocument->version); ?> • <?php echo e($latestDocument->file_original_name); ?>

                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">Belum ada file</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if($latestDocument): ?>
                                            <a href="<?php echo e(route('tenant.publication-progress.download-document', $latestDocument->id)); ?>" class="btn btn-outline-primary btn-sm" target="<?php echo e($latestDocument->is_link ? '_blank' : '_self'); ?>">
                                                <i class="bi <?php echo e($latestDocument->is_link ? 'bi-box-arrow-up-right' : 'bi-download'); ?> me-1"></i> <?php echo e($latestDocument->is_link ? 'Buka Link' : 'Download'); ?>

                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php echo $__env->make('shared._sprp_view_modal', [
                'sprp' => $sprp,
                'modalId' => $sprpModalId,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center py-5 bg-white border rounded-4 shadow-sm">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <div class="fw-bold mt-2">Belum ada publikasi siap rilis</div>
                <div class="text-muted small">Publikasi akan tampil setelah proses finalisasi rilis selesai.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <?php echo e($publications->links('pagination::bootstrap-5')); ?>

    </div>

    <?php echo $__env->make('shared._package_download_loader', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/ready_release/index.blade.php ENDPATH**/ ?>