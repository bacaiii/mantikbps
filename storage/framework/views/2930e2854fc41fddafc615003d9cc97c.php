<?php $__env->startSection('title', 'Manajemen Publikasi'); ?>

<?php $__env->startSection('content'); ?>
    <?php
        $makeSortUrl = function ($column) use ($sortBy, $sortDir) {
            $newDir = ($sortBy === $column && $sortDir === 'asc') ? 'desc' : 'asc';

            return route('tenant.publications.index', array_merge(request()->query(), [
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

        $formatDateStack = function ($date) {
            if (!$date) {
                return '<span class="text-muted">-</span>';
            }

            return '<div class="date-stack">
                        <span class="date-main">' . e($date->translatedFormat('j F')) . '</span>
                        <span class="date-year">' . e($date->translatedFormat('Y')) . '</span>
                    </div>';
        };
    ?>

    <div class="card table-card">
        <div class="card-header bg-white border-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-0 fw-bold">Daftar Publikasi ARC dan Non-ARC</h5>
                    <small class="text-muted">Kelola judul, kategori, periode, jadwal, Akurasi, dan Estimasi Nomor Publikasi.</small>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="submit" form="publication-filter-form" formaction="<?php echo e(route('tenant.publications.monthly-report')); ?>" class="btn btn-outline-primary">
                        <i class="bi bi-file-earmark-text me-1"></i> Rekap Laporan Publikasi
                    </button>
                    <a href="<?php echo e(route('tenant.publications.create')); ?>" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Publikasi
                    </a>
                </div>
            </div>

            <form method="GET" id="publication-filter-form" class="row g-2 mt-3">
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
                    <select name="kategori" class="form-select">
                        <option value="">Semua Kategori</option>
                        <option value="ARC" <?php echo e(request('kategori') === 'ARC' ? 'selected' : ''); ?>>ARC</option>
                        <option value="Non-ARC" <?php echo e(request('kategori') === 'Non-ARC' ? 'selected' : ''); ?>>Non-ARC</option>
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

                <div class="col-md-2">
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
            <div class="alert alert-info mb-3" style="background: rgba(13, 202, 240, 0.18); border: 1px solid rgba(13, 202, 240, 0.45);">
                <strong>Keterangan fitur:</strong>
                <div class="mt-2"><i class="bi bi-plus-circle text-primary me-1"></i><strong>Tambah Publikasi</strong> digunakan untuk menambahkan judul publikasi ARC atau Non-ARC.</div>
                <div class="mt-1"><i class="bi bi-search text-primary me-1"></i><strong>Filter</strong> digunakan untuk menampilkan data berdasarkan tahun, bulan, kategori, status, atau kata kunci tertentu.</div>
                <div class="mt-1"><i class="bi bi-file-earmark-text text-primary me-1"></i><strong>Rekap Laporan Publikasi</strong> digunakan untuk melihat rincian publikasi sesuai tahun dan bulan yang dipilih.</div>
                <div class="mt-1"><i class="bi bi-arrow-down-up text-secondary me-1"></i><strong>Header tabel</strong> dapat diklik untuk mengurutkan data berdasarkan kolom.</div>
                <div class="mt-1"><i class="bi bi-pencil-square text-warning me-1"></i><strong>Edit</strong> digunakan untuk memperbarui data publikasi.</div>
                <div class="mt-1"><i class="bi bi-trash text-danger me-1"></i><strong>Delete</strong> digunakan untuk menghapus data publikasi.</div>
            </div>

            <div class="table-fit-wrapper">
                <table class="table align-middle table-bordered table-clean publication-fit-table publication-management-table">
                    <colgroup>
                        <col class="col-title">
                        <col class="col-category">
                        <col class="col-date">
                        <col class="col-date">
                        <col class="col-check-date">
                        <col class="col-start-date">
                        <col class="col-status">
                        <col class="col-action">
                    </colgroup>
                    <thead>
                        <tr>
                            <?php $__currentLoopData = [
                                'nama_publikasi' => 'Nama Publikasi',
                                'kategori' => 'Kategori',
                                'jadwal_rilis' => 'Jadwal Rilis',
                                'jadwal_upload' => 'Jadwal Upload',
                                'jadwal_mulai_pemeriksaan' => 'Mulai Pemeriksaan',
                                'jadwal_mulai_penyusunan' => 'Mulai Penyusunan',
                                'status' => 'Status',
                            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $column => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="<?php echo e($sortThClass($column)); ?>">
                                    <a href="<?php echo e($makeSortUrl($column)); ?>" class="<?php echo e($sortLinkClass($column)); ?>">
                                        <span><?php echo e($label); ?></span>
                                        <?php echo $sortIcon($column); ?>

                                    </a>
                                </th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <th>Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $publications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $publication): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="name-cell"><?php echo e($publication->nama_publikasi); ?></td>
                                <td>
                                    <span class="badge <?php echo e($publication->kategori === 'ARC' ? 'bg-primary' : 'bg-secondary'); ?> compact-badge">
                                        <?php echo e($publication->kategori); ?>

                                    </span>
                                </td>
                                <td><?php echo $formatDateStack($publication->jadwal_rilis); ?></td>
                                <td><?php echo $formatDateStack($publication->jadwal_upload); ?></td>
                                <td><?php echo $formatDateStack($publication->jadwal_mulai_pemeriksaan); ?></td>
                                <td><?php echo $formatDateStack($publication->jadwal_mulai_penyusunan); ?></td>
                                <td>
                                    <span class="status-chip <?php echo e($publication->status_css_class); ?>"><?php echo e($publication->status_label); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="<?php echo e(route('tenant.publications.edit', $publication->id)); ?>"
                                           class="btn btn-warning btn-sm table-action-btn"
                                           title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>

                                        <form action="<?php echo e(route('tenant.publications.destroy', $publication->id)); ?>"
                                              method="POST"
                                              onsubmit="return confirm('Yakin ingin menghapus publikasi ini?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button class="btn btn-danger btn-sm table-action-btn" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Belum ada data publikasi.</td>
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

<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/publications/index.blade.php ENDPATH**/ ?>