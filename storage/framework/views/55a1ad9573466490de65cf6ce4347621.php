<?php $__env->startSection('title', 'Progres Publikasi'); ?>

<?php
    $formatDateStack = function ($date) {
        if (!$date) {
            return '<span class="text-muted">-</span>';
        }

        return '<div class="date-stack">
                    <span class="date-main">' . e($date->translatedFormat('j F')) . '</span>
                    <span class="date-year">' . e($date->translatedFormat('Y')) . '</span>
                </div>';
    };

    $remainingDays = function ($date) {
        if (!$date) {
            return 0;
        }

        return max(0, now()->startOfDay()->diffInDays($date->copy()->startOfDay(), false));
    };

    $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('tenant.publication-progress.index', array_merge(request()->query(), [
            'sort_by' => $column,
            'sort_dir' => $newDir,
        ]));
    };

    $sortIcon = function ($column) use ($sortBy, $sortDir) {
        if ($sortBy !== $column) {
            return '';
        }

        return $sortDir === 'asc'
            ? '<i class="bi bi-caret-up-fill sort-icon"></i>'
            : '<i class="bi bi-caret-down-fill sort-icon"></i>';
    };

    $sortThClass = fn ($column) => $sortBy === $column ? 'sort-active' : '';
    $sortLinkClass = fn ($column) => $sortBy === $column ? 'sort-link active' : 'sort-link';
?>

<?php $__env->startSection('content'); ?>
    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div>
                <h5 class="mb-0 fw-bold">Progres Publikasi</h5>
                <small class="text-muted">Pantau jadwal, dokumen, log aktivitas, dan bantuan upload tim penyusun.</small>
            </div>

            <form method="GET" class="row g-2 mt-3">
                <?php if(request('sort_by')): ?>
                    <input type="hidden" name="sort_by" value="<?php echo e(request('sort_by')); ?>">
                <?php endif; ?>
                <?php if(request('sort_dir')): ?>
                    <input type="hidden" name="sort_dir" value="<?php echo e(request('sort_dir')); ?>">
                <?php endif; ?>

                <div class="col-md-2">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <?php $__currentLoopData = $yearOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($year); ?>" <?php echo e((int) $selectedYear === (int) $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="bulan" class="form-select">
                        <option value="">Semua Bulan</option>
                        <?php $__currentLoopData = $monthOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthNumber => $monthName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($monthNumber); ?>" <?php echo e((string) $selectedMonth === (string) $monthNumber ? 'selected' : ''); ?>><?php echo e($monthName); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <option value="penyusunan" <?php echo e(request('status') === 'penyusunan' ? 'selected' : ''); ?>>Penyusunan</option>
                        <option value="pemeriksaan_konten" <?php echo e(request('status') === 'pemeriksaan_konten' ? 'selected' : ''); ?>>Pemeriksaan Konten</option>
                        <option value="pemeriksaan_layout" <?php echo e(request('status') === 'pemeriksaan_layout' ? 'selected' : ''); ?>>Pemeriksaan Layout</option>
                        <option value="pemeriksaan_infografis" <?php echo e(request('status') === 'pemeriksaan_infografis' ? 'selected' : ''); ?>>Pemeriksaan Infografis</option>
                        <option value="persetujuan_pimpinan" <?php echo e(request('status') === 'persetujuan_pimpinan' ? 'selected' : ''); ?>>Persetujuan Pimpinan</option>
                        <option value="operator_website" <?php echo e(request('status') === 'operator_website' ? 'selected' : ''); ?>>Finalisasi Rilis</option>
                        <option value="siap_rilis" <?php echo e(request('status') === 'siap_rilis' ? 'selected' : ''); ?>>Siap Rilis</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" value="<?php echo e(request('q')); ?>" placeholder="Cari nama publikasi...">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3"
                style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45); border-radius: 16px;">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2">
                    <i class="bi bi-search text-primary me-1"></i>
                    <strong>Pencarian</strong> digunakan untuk menemukan publikasi berdasarkan nama publikasi.
                </div>
                <div class="mt-1">
                    <i class="bi bi-funnel text-primary me-1"></i>
                    <strong>Filter</strong> digunakan untuk menyaring publikasi berdasarkan tahun, bulan, dan tahapan proses.
                </div>
                <div class="mt-1">
                    <i class="bi bi-arrow-down-up text-secondary me-1"></i>
                    <strong>Header tabel</strong> dapat diklik untuk mengurutkan data berdasarkan kolom.
                </div>
                <div class="mt-1">
                    <i class="bi bi-eye text-primary me-1"></i>
                    <strong>Detail</strong> digunakan untuk melihat rincian publikasi dan dokumen yang telah diunggah.
                </div>
                <div class="mt-1">
                    <i class="bi bi-clock-history text-info me-1"></i>
                    <strong>Log History</strong> digunakan untuk melihat riwayat aktivitas penyusunan, upload dokumen, pemeriksaan, dan persetujuan.
                </div>
                <div class="mt-1">
                    <i class="bi bi-people text-success me-1"></i>
                    <strong>Tim Penyusun</strong> membuka form bantuan upload naskah penyusunan apabila tim penyusun berkendala.
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-clean align-middle progress-publication-table">
                    <colgroup>
                        <col class="col-title">
                        <col class="col-release-date">
                        <col class="col-upload-date">
                        <col class="col-check-start-date">
                        <col class="col-start-date">
                        <col class="col-status">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr>
                            <?php $__currentLoopData = [
                                'nama_publikasi' => 'Judul Publikasi',
                                'jadwal_rilis' => 'Tanggal Rilis',
                                'jadwal_upload' => 'Jadwal Upload',
                                'jadwal_mulai_pemeriksaan' => 'Mulai Pemeriksaan',
                                'jadwal_mulai_penyusunan' => 'Mulai Penyusunan',
                                'status' => 'Status',
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="<?php echo e($sortThClass($column)); ?> <?php echo e(in_array($column, ['jadwal_mulai_pemeriksaan', 'jadwal_mulai_penyusunan'], true) ? 'col-check-start-date' : ''); ?>">
                                    <a href="<?php echo e($makeSortUrl($column)); ?>" class="<?php echo e($sortLinkClass($column)); ?>">
                                        <span><?php echo in_array($label, ['Mulai Pemeriksaan', 'Mulai Penyusunan'], true) ? str_replace(' ', '<br>', e($label)) : e($label); ?></span>
                                        <?php echo $sortIcon($column); ?>

                                    </a>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <th class="col-action">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $publications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $publication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="name-cell" title="<?php echo e($publication->nama_publikasi); ?>">
                                    <div class="progress-title-text"><?php echo e($publication->nama_publikasi); ?></div>
                                </td>
                                <td>
                                    <?php echo $formatDateStack($publication->jadwal_rilis); ?>

                                    <span class="remaining-days-chip">Sisa <?php echo e($remainingDays($publication->jadwal_rilis)); ?> hari</span>
                                </td>
                                <td><?php echo $formatDateStack($publication->jadwal_upload); ?></td>
                                <td><?php echo $formatDateStack($publication->jadwal_mulai_pemeriksaan); ?></td>
                                <td><?php echo $formatDateStack($publication->jadwal_mulai_penyusunan); ?></td>
                                <td><span class="status-chip <?php echo e($publication->status_css_class); ?>"><?php echo e($publication->status_label); ?></span></td>
                                <td>
                                    <div class="progress-action-group">
                                        <a href="<?php echo e(route('tenant.publication-progress.show', $publication->id)); ?>" class="btn btn-primary btn-sm table-action-btn" title="Detail">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo e(route('tenant.publication-progress.history', $publication->id)); ?>" class="btn btn-secondary btn-sm table-action-btn" title="Log History">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                        <a href="<?php echo e(route('tenant.publication-progress.author-team', $publication->id)); ?>" class="btn btn-success btn-sm table-action-btn" title="Tim Penyusun">
                                            <i class="bi bi-people"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada data progres publikasi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                <?php echo e($publications->links('pagination::bootstrap-5')); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/publication_progress/index.blade.php ENDPATH**/ ?>