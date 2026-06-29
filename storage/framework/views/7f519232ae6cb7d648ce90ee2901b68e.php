<?php $__env->startSection('title', 'Tim Kerja Publikasi'); ?>

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

        return route('tenant.team-allocations.index', array_merge(request()->query(), [
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
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Tim Kerja Publikasi</h5>
                    <small class="text-muted">Kelola alokasi template tim kerja pada setiap publikasi.</small>
                </div>

                <a href="<?php echo e(route('tenant.team-allocations.create')); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i> Alokasi Tim
                </a>
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

                <div class="col-md-4">
                    <input type="text" name="q" class="form-control" value="<?php echo e(request('q')); ?>" placeholder="Cari nama tim / judul publikasi...">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-outline-primary w-100">
                        <i class="bi bi-search me-1"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body">
            <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2"><i class="bi bi-plus-circle text-primary me-1"></i><strong>Alokasi Tim</strong> digunakan untuk memilih judul publikasi dan menerapkan template tim kerja.</div>
                <div class="mt-1"><i class="bi bi-search text-primary me-1"></i><strong>Filter</strong> digunakan untuk mencari berdasarkan tahun, bulan, nama tim, atau judul publikasi.</div>
                <div class="mt-1"><i class="bi bi-arrow-down-up text-secondary me-1"></i><strong>Header tabel</strong> dapat diklik untuk mengurutkan data berdasarkan kolom.</div>
                <div class="mt-1"><i class="bi bi-pencil-square text-warning me-1"></i><strong>Assign Tim</strong> digunakan untuk mengatur ulang nama tim, anggota, dan tugas pada publikasi.</div>
                <div class="mt-1"><i class="bi bi-trash text-danger me-1"></i><strong>Delete</strong> digunakan untuk menghapus tim kerja.</div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle table-clean team-work-table">
                    <thead>
                        <tr>
                            <th class="col-no <?php echo e($sortThClass('created_at')); ?>">
                                <a href="<?php echo e($makeSortUrl('created_at')); ?>" class="<?php echo e($sortLinkClass('created_at')); ?>">
                                    <span>No</span><?php echo $sortIcon('created_at'); ?>

                                </a>
                            </th>
                            <th class="<?php echo e($sortThClass('name')); ?>">
                                <a href="<?php echo e($makeSortUrl('name')); ?>" class="<?php echo e($sortLinkClass('name')); ?>">
                                    <span>Nama Tim</span><?php echo $sortIcon('name'); ?>

                                </a>
                            </th>
                            <th class="<?php echo e($sortThClass('publication_name')); ?>">
                                <a href="<?php echo e($makeSortUrl('publication_name')); ?>" class="<?php echo e($sortLinkClass('publication_name')); ?>">
                                    <span>Judul Publikasi</span><?php echo $sortIcon('publication_name'); ?>

                                </a>
                            </th>
                            <th class="col-release-date <?php echo e($sortThClass('jadwal_rilis')); ?>">
                                <a href="<?php echo e($makeSortUrl('jadwal_rilis')); ?>" class="<?php echo e($sortLinkClass('jadwal_rilis')); ?>">
                                    <span>Tanggal Rilis</span><?php echo $sortIcon('jadwal_rilis'); ?>

                                </a>
                            </th>
                            <th class="col-action">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $teams; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $team): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="col-no"><?php echo e($teams->firstItem() + $index); ?></td>
                                <td class="team-name-cell"><?php echo e($team->name); ?></td>
                                <td class="publication-title-cell"><?php echo e(optional($team->publication)->nama_publikasi ?? '-'); ?></td>
                                <td><?php echo $formatDateStack(optional($team->publication)->jadwal_rilis); ?></td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?php echo e(route('tenant.team-allocations.assign', $team->id)); ?>"
                                           class="btn btn-success btn-sm"
                                           title="Assign Tim">
                                            <i class="bi bi-person-check me-1"></i> Assign Tim
                                        </a>

                                        <form action="<?php echo e(route('tenant.team-allocations.destroy', $team->id)); ?>"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus alokasi tim kerja ini?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="btn btn-danger btn-sm table-action-btn" title="Hapus Tim">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">Belum ada tim kerja.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-end">
                <?php echo e($teams->links('pagination::bootstrap-5')); ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/team_allocations/index.blade.php ENDPATH**/ ?>