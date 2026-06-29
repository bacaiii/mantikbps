<?php $__env->startSection('title', 'Tambah Publikasi'); ?>

<?php $__env->startSection('content'); ?>
    <div class="card form-card">
        <div class="card-header bg-white border-0">
            <h5 class="mb-0 fw-bold">Form Tambah Publikasi</h5>
            <small class="text-muted">Input data Publikasi yang diperlukan.</small>
        </div>

        <div class="card-body">
            <?php echo $__env->make('tenant.publications._form', [
                'formAction' => route('tenant.publications.store'),
                'formMethod' => 'POST',
                'submitLabel' => 'Simpan Publikasi',
                'publication' => $publication,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.tenant', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/tenant/publications/create.blade.php ENDPATH**/ ?>