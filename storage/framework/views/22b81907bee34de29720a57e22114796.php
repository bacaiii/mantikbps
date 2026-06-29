<?php $__env->startSection('title', 'Monitoring dan Evaluasi Kabupaten/Kota'); ?>

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

    $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
        $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

        return route('tenant.monitoring.index', array_merge(request()->query(), [
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
    <div class="card table-card monitoring-control-card mb-4">
        <div class="card-header bg-white border-0">
            <div>
                <h5 class="mb-0 fw-bold">Monitoring Publikasi Kabupaten/Kota</h5>
                <small class="text-muted">Rekap publikasi kabupaten/kota berdasarkan tahun, bulan rilis, status proses, dan kelengkapan dokumen publikasi.</small>
            </div>

            <form method="GET" class="row g-2 mt-3 monitoring-filter-row align-items-center">
                <?php if(request('sort_by')): ?>
                    <input type="hidden" name="sort_by" value="<?php echo e(request('sort_by')); ?>">
                <?php endif; ?>
                <?php if(request('sort_dir')): ?>
                    <input type="hidden" name="sort_dir" value="<?php echo e(request('sort_dir')); ?>">
                <?php endif; ?>

                <div class="col-md-2 col-xl-1">
                    <select name="tahun" class="form-select" onchange="this.form.submit()">
                        <?php $__currentLoopData = $yearOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($year); ?>" <?php echo e((int) $selectedYear === (int) $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-2 col-xl-2">
                    <select name="bulan" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        <?php $__currentLoopData = $monthOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthNumber => $monthName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($monthNumber); ?>" <?php echo e((int) $selectedMonth === (int) $monthNumber ? 'selected' : ''); ?>><?php echo e($monthName); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-3 col-xl-2">
                    <select name="wilayah" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Wilayah</option>
                        <?php $__currentLoopData = $wilayahOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $wilayah): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($wilayah); ?>" <?php echo e(request('wilayah') === $wilayah ? 'selected' : ''); ?>>
                                <?php echo e($wilayah); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div class="col-md-3 col-xl-2">
                    <select name="status" class="form-select" onchange="this.form.submit()">
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

                <div class="col-md-4 col-xl-3">
                    <input type="text" name="q" class="form-control" value="<?php echo e(request('q')); ?>" placeholder="Cari nama publikasi...">
                </div>

                <div class="col-md-2 col-xl-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="monitoring-summary-grid mb-4">
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-primary-subtle text-primary"><i class="bi bi-journal-text"></i></span>
            <div>
                <small>Total Publikasi</small>
                <strong><?php echo e($summary['total']); ?></strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-info-subtle text-info"><i class="bi bi-hourglass-split"></i></span>
            <div>
                <small>Dalam Proses</small>
                <strong><?php echo e($summary['dalam_proses']); ?></strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></span>
            <div>
                <small>Siap Rilis</small>
                <strong><?php echo e($summary['siap_rilis']); ?></strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-success-subtle text-success"><i class="bi bi-folder-check"></i></span>
            <div>
                <small>Dokumen Lengkap</small>
                <strong><?php echo e($summary['lengkap']); ?></strong>
            </div>
        </div>
        <div class="monitoring-summary-card">
            <span class="monitoring-summary-icon bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i></span>
            <div>
                <small>Belum Lengkap</small>
                <strong><?php echo e($summary['belum_lengkap']); ?></strong>
            </div>
        </div>
    </div>

    <div class="card table-card mb-4">
        <div class="card-header bg-white border-0 d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div>
                <h6 class="mb-0 fw-bold">Rekap Bulanan per Kabupaten/Kota</h6>
                <small class="text-muted">Mengganti pemantauan manual per bulan dengan rekap jumlah publikasi, siap rilis, dan kelengkapan dokumen.</small>
            </div>
            <div class="monitoring-legend">
                <span><i class="bi bi-circle-fill text-primary"></i> Total Publikasi</span>
                <span><i class="bi bi-circle-fill text-success"></i> Siap</span>
                <span><i class="bi bi-circle-fill text-warning"></i> Belum lengkap</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle monitoring-month-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Wilayah</th>
                            <?php $__currentLoopData = $monthOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $monthName): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th><?php echo e($monthName); ?></th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <th>Total<br>Publikasi</th>
                            <th>Total<br>ARC/Non-ARC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $regionalRecap; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recap): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <?php
                                    $recapCode = $recap['tenant']->code ?? null;
                                    $recapCodeDisplay = $recapCode && stripos($recapCode, 'tenant') === false
                                        ? $recapCode
                                        : ($recap['tenant']->wilayah ?? $recap['tenant']->name ?? '-');
                                ?>
                                <td class="fw-bold text-center"><?php echo e($recapCodeDisplay); ?></td>
                                <td class="fw-semibold monitoring-region-name"><?php echo e($recap['tenant']->wilayah ?? $recap['tenant']->name); ?></td>
                                <?php $__currentLoopData = $recap['months']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $month): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <td class="text-center">
                                        <?php if($month['total'] > 0): ?>
                                            <div class="monitoring-month-cell <?php echo e($month['incomplete'] > 0 ? 'has-warning' : 'is-complete'); ?>">
                                                <span class="monitoring-month-main"><?php echo e($month['ready']); ?>/<?php echo e($month['total']); ?></span>
                                                <small><?php echo e($month['incomplete']); ?> belum lengkap</small>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                <td class="text-center">
                                    <div class="monitoring-total-stack">
                                        <strong><?php echo e($recap['total']); ?></strong>
                                        <small>(<?php echo e($recap['ready']); ?>/<?php echo e($recap['total']); ?> selesai)</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="monitoring-total-stack">
                                        <strong><?php echo e($recap['arc']); ?>/<?php echo e($recap['non_arc']); ?></strong>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="16" class="text-center text-muted">Belum ada data wilayah kabupaten/kota.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div>
                <h6 class="mb-0 fw-bold">Detail Kelengkapan Publikasi</h6>
                <small class="text-muted">Menampilkan status alur dan kelengkapan dokumen setiap publikasi kabupaten/kota.</small>
            </div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle table-clean monitoring-table monitoring-detail-table">
                    <colgroup>
                        <col class="col-no">
                        <col class="col-region">
                        <col class="col-title">
                        <col class="col-category">
                        <col class="col-date">
                        <col class="col-status">
                        <col class="col-complete">
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="col-no <?php echo e($sortThClass('created_at')); ?>">
                                <a href="<?php echo e($makeSortUrl('created_at')); ?>" class="<?php echo e($sortLinkClass('created_at')); ?>">
                                    <span>No</span><?php echo $sortIcon('created_at'); ?>

                                </a>
                            </th>
                            <th class="col-region <?php echo e($sortThClass('wilayah')); ?>">
                                <a href="<?php echo e($makeSortUrl('wilayah')); ?>" class="<?php echo e($sortLinkClass('wilayah')); ?>">
                                    <span>Wilayah</span><?php echo $sortIcon('wilayah'); ?>

                                </a>
                            </th>
                            <th class="col-title <?php echo e($sortThClass('nama_publikasi')); ?>">
                                <a href="<?php echo e($makeSortUrl('nama_publikasi')); ?>" class="<?php echo e($sortLinkClass('nama_publikasi')); ?>">
                                    <span>Nama Publikasi</span><?php echo $sortIcon('nama_publikasi'); ?>

                                </a>
                            </th>
                            <th class="col-category <?php echo e($sortThClass('kategori')); ?>">
                                <a href="<?php echo e($makeSortUrl('kategori')); ?>" class="<?php echo e($sortLinkClass('kategori')); ?>">
                                    <span>Kategori</span><?php echo $sortIcon('kategori'); ?>

                                </a>
                            </th>
                            <th class="col-date <?php echo e($sortThClass('jadwal_rilis')); ?>">
                                <a href="<?php echo e($makeSortUrl('jadwal_rilis')); ?>" class="<?php echo e($sortLinkClass('jadwal_rilis')); ?>">
                                    <span>Jadwal Rilis</span><?php echo $sortIcon('jadwal_rilis'); ?>

                                </a>
                            </th>
                            <th class="col-status <?php echo e($sortThClass('status')); ?>">
                                <a href="<?php echo e($makeSortUrl('status')); ?>" class="<?php echo e($sortLinkClass('status')); ?>">
                                    <span>Status</span><?php echo $sortIcon('status'); ?>

                                </a>
                            </th>
                            <th class="col-complete">Kelengkapan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $publications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $publication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="col-no"><?php echo e($publications->firstItem() + $index); ?></td>
                                <td class="col-region-cell">
                                    <?php
                                        $tenantCode = optional($publication->tenant)->code;
                                        $tenantCodeDisplay = $tenantCode && stripos($tenantCode, 'tenant') === false
                                            ? $tenantCode
                                            : (optional($publication->tenant)->wilayah ?? '-');
                                    ?>
                                    <span class="d-block fw-bold"><?php echo e($tenantCodeDisplay); ?></span>
                                    <small><?php echo e(optional($publication->tenant)->wilayah); ?></small>
                                </td>
                                <td class="name-cell">
                                    <?php echo e($publication->nama_publikasi); ?>

                                    <div class="monitoring-date-chips mt-2">
                                        <span>Penyusunan: <?php echo e($publication->jadwal_mulai_penyusunan ? $publication->jadwal_mulai_penyusunan->format('d/m/y') : '-'); ?></span>
                                        <span>Pemeriksaan: <?php echo e($publication->jadwal_mulai_pemeriksaan ? $publication->jadwal_mulai_pemeriksaan->format('d/m/y') : '-'); ?></span>
                                        <span>Upload: <?php echo e($publication->jadwal_upload ? $publication->jadwal_upload->format('d/m/y') : '-'); ?></span>
                                    </div>
                                </td>
                                <td class="col-category-cell">
                                    <span class="badge <?php echo e($publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary'); ?> compact-badge">
                                        <?php echo e($publication->kategori); ?>

                                    </span>
                                </td>
                                <td class="col-date-cell"><?php echo $formatDateStack($publication->jadwal_rilis); ?></td>
                                <td class="col-status-cell">
                                    <span class="status-chip <?php echo e($publication->status_css_class); ?>"><?php echo e($publication->status_label); ?></span>
                                </td>
                                <td class="monitoring-complete-cell">
                                    <div class="monitoring-complete-head <?php echo e($publication->getAttribute('monitoring_complete') ? 'complete' : 'warning'); ?>">
                                        <strong><?php echo e($publication->getAttribute('monitoring_available_total')); ?>/<?php echo e($publication->getAttribute('monitoring_required_total')); ?></strong>
                                        <span><?php echo e($publication->getAttribute('monitoring_complete') ? 'Lengkap' : 'Belum Lengkap'); ?></span>
                                    </div>
                                    <?php if(!$publication->getAttribute('monitoring_complete')): ?>
                                        <div class="monitoring-missing-list">
                                            <?php $__currentLoopData = $publication->getAttribute('monitoring_missing_items'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $missingItem): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <span><?php echo e($missingItem); ?></span>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Belum ada publikasi kabupaten/kota.</td>
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

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/monitoring/index.blade.php ENDPATH**/ ?>