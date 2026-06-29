<?php $__env->startSection('title', 'Knowledge'); ?>

<?php $__env->startSection('content'); ?>
    <div class="mb-4">
        <div>
            <span class="badge bg-primary-subtle text-primary mb-2">
                <i class="bi bi-link-45deg me-1"></i> Knowledge Pegawai
            </span>
            <h4 class="fw-bold mb-1">Materi dan Link Knowledge</h4>
            <p class="mb-0 text-muted">Daftar referensi, pedoman, template, atau materi pendukung yang disediakan admin.</p>
        </div>
    </div>

    <div class="accordion knowledge-drawer-list" id="employeeKnowledgeAccordion">
        <?php $__empty_1 = true; $__currentLoopData = $knowledgeLinks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $knowledgeLink): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $category = $knowledgeLink->category ?: 'Umum';
                $icon = str_contains(strtolower($category), 'video') || str_contains(strtolower($knowledgeLink->url), 'youtube') ? 'bi-play-btn'
                    : (str_contains(strtolower($category), 'dokumen') || str_contains(strtolower($knowledgeLink->url), 'drive') ? 'bi-file-earmark-text'
                    : (str_contains(strtolower($category), 'template') ? 'bi-layout-text-window-reverse'
                    : 'bi-link-45deg'));
                $collapseId = 'knowledgeDrawer' . $knowledgeLink->id;
            ?>

            <div class="accordion-item border-0 bg-transparent mb-3">
                <h2 class="accordion-header" id="heading<?php echo e($collapseId); ?>">
                    <button class="accordion-button collapsed shadow-sm rounded-4 py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo e($collapseId); ?>" aria-expanded="false" aria-controls="<?php echo e($collapseId); ?>">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary me-3" style="width: 38px; height: 38px; flex: 0 0 38px;">
                            <i class="bi <?php echo e($icon); ?>"></i>
                        </span>
                        <span class="flex-grow-1">
                            <span class="d-block fw-bold text-dark"><?php echo e($knowledgeLink->title); ?></span>
                            <small class="text-muted"><?php echo e($category); ?></small>
                        </span>
                    </button>
                </h2>

                <div id="<?php echo e($collapseId); ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo e($collapseId); ?>" data-bs-parent="#employeeKnowledgeAccordion">
                    <div class="accordion-body bg-white border rounded-4 mt-2 shadow-sm">
                        <p class="text-muted mb-3"><?php echo e($knowledgeLink->description ?: 'Belum ada deskripsi untuk materi ini.'); ?></p>
                        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap">
                            <small class="text-muted">
                                <i class="bi bi-globe2 me-1"></i>
                                <?php echo e(\Illuminate\Support\Str::limit($knowledgeLink->url, 72)); ?>

                            </small>
                            <a href="<?php echo e($knowledgeLink->url); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Link
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-center py-5 bg-white border rounded-4 shadow-sm">
                <i class="bi bi-inbox fs-2 text-muted"></i>
                <div class="fw-bold mt-2">Belum ada knowledge aktif</div>
                <div class="text-muted small">Materi knowledge akan tampil setelah admin menambahkan link aktif.</div>
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 d-flex justify-content-end">
        <?php echo e($knowledgeLinks->links('pagination::bootstrap-5')); ?>

    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.employee', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/employee/knowledge/index.blade.php ENDPATH**/ ?>