<?php $__env->startSection('title', 'Rekap Hasil Kerja'); ?>

<?php $__env->startSection('content'); ?>
    <div class="card table-card mb-4 employee-work-report-page">
        <div class="card-header bg-white border-0 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <h5 class="mb-0 fw-bold">Rekap Hasil Kerja Pegawai</h5>
                <small class="text-muted">Rincian kontribusi kerja Anda pada publikasi siap rilis.</small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="<?php echo e(route('employee.ready-release.index')); ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
                <a href="<?php echo e(route('employee.ready-release.work-report.pdf', $publicationTeam->id)); ?>" class="btn btn-primary">
                    <i class="bi bi-download me-1"></i> Download PDF
                </a>
            </div>
        </div>

        <div class="card-body">
            <div class="summary-panel employee-work-hero mb-4">
                <div class="summary-pill mb-3">Bukti Kerja Saya</div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Nama Pegawai</div>
                        <div class="fw-bold"><?php echo e(auth()->user()->name); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Email</div>
                        <div class="fw-bold"><?php echo e(auth()->user()->email ?: '-'); ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Wilayah</div>
                        <div class="fw-bold"><?php echo e(optional(auth()->user()->tenant)->wilayah ?? '-'); ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-4 employee-work-metric-grid">
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Nama Publikasi</div>
                        <div class="fw-bold"><?php echo e($publication->nama_publikasi); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Tim Kerja</div>
                        <div class="fw-bold"><?php echo e($publicationTeam->name ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Peran Pegawai</div>
                        <div class="fw-bold"><?php echo e($myAssignments->pluck('assignment_role_label')->implode(', ') ?: '-'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Kategori / Periode / Akurasi</div>
                        <div class="fw-bold"><?php echo e($publication->kategori); ?> • <?php echo e($publication->periode ?? '-'); ?> • <?php echo e($publication->akurasi_publikasi ?? '-'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Jadwal Rilis</div>
                        <div class="fw-bold"><?php echo e($publication->jadwal_rilis ? $publication->jadwal_rilis->translatedFormat('d F Y') : '-'); ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100 employee-work-metric-card">
                        <div class="small text-muted">Status Publikasi</div>
                        <div><span class="status-chip <?php echo e($publication->status_css_class); ?>"><?php echo e($publication->status_label); ?></span></div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm employee-work-activity-card">
                <div class="card-header bg-white border-0">
                    <h6 class="mb-0 fw-bold">Rincian Aktivitas Kerja</h6>
                    <small class="text-muted">Aktivitas diambil dari penugasan, unggah dokumen, submit naskah, pemeriksaan, dan riwayat proses yang terkait dengan akun Anda.</small>
                </div>
                <div class="card-body">
                    <?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="border rounded-3 p-3 mb-2 bg-light employee-work-activity-item">
                            <div class="d-flex justify-content-between gap-3 flex-wrap">
                                <div class="fw-bold"><?php echo e($activity['aktivitas']); ?></div>
                                <small class="text-muted"><?php echo e($activity['tanggal']); ?></small>
                            </div>
                            <?php if(!empty($activity['keterangan'])): ?>
                                <div class="small text-muted mt-1"><?php echo e($activity['keterangan']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center text-muted py-4">
                            Belum ada aktivitas kerja yang tercatat pada publikasi ini.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.employee', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/employee/ready_release/work_report.blade.php ENDPATH**/ ?>