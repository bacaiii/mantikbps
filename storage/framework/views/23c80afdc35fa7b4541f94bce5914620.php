<?php
    $authUser = auth()->user();
    $unreadNotifications = $authUser
        ? $authUser->unreadNotifications()->latest()->limit(8)->get()
        : collect();
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
?>

<div class="dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
        <i class="bi bi-bell"></i>
        <?php if($unreadCount > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                <?php echo e($unreadCount > 9 ? '9+' : $unreadCount); ?>

            </span>
        <?php endif; ?>
    </button>

    <div class="dropdown-menu dropdown-menu-end notification-menu p-0 shadow border-0">
        <div class="px-3 py-3 border-bottom d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold">Notifikasi</div>
                <small class="text-muted"><?php echo e($unreadCount); ?> belum dibaca</small>
            </div>

            <?php if($unreadCount > 0): ?>
                <form action="<?php echo e(route('notifications.read-all')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button class="btn btn-sm btn-outline-primary" type="submit">Baca semua</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="notification-list">
            <?php $__empty_1 = true; $__currentLoopData = $unreadNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $title = data_get($notification->data, 'title', 'Notifikasi');
                    $message = data_get($notification->data, 'message', '-');
                    $icon = data_get($notification->data, 'icon', 'bi-bell');
                ?>

                <form action="<?php echo e(route('notifications.read', $notification->id)); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button class="dropdown-item notification-item text-start" type="submit">
                        <span class="notification-icon">
                            <i class="bi <?php echo e($icon); ?>"></i>
                        </span>
                        <span class="notification-content">
                            <span class="notification-title"><?php echo e($title); ?></span>
                            <span class="notification-message"><?php echo e($message); ?></span>
                            <span class="notification-time"><?php echo e($notification->created_at->diffForHumans()); ?></span>
                        </span>
                    </button>
                </form>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="text-center text-muted py-4 px-3">
                    <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                    Tidak ada notifikasi baru.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php /**PATH C:\laragon\www\manajemen-publikasi-statistik\resources\views/partials/notification-dropdown.blade.php ENDPATH**/ ?>