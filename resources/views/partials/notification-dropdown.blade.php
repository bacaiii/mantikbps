@php
    $authUser = auth()->user();
    $unreadNotifications = $authUser
        ? $authUser->unreadNotifications()->latest()->limit(8)->get()
        : collect();
    $unreadCount = $authUser ? $authUser->unreadNotifications()->count() : 0;
@endphp

<div class="dropdown">
    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Notifikasi">
        <i class="bi bi-bell"></i>
        @if($unreadCount > 0)
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div class="dropdown-menu dropdown-menu-end notification-menu p-0 shadow border-0">
        <div class="px-3 py-3 border-bottom d-flex align-items-center justify-content-between gap-2">
            <div>
                <div class="fw-bold">Notifikasi</div>
                <small class="text-muted">{{ $unreadCount }} belum dibaca</small>
            </div>

            @if($unreadCount > 0)
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button class="btn btn-sm btn-outline-primary" type="submit">Baca semua</button>
                </form>
            @endif
        </div>

        <div class="notification-list">
            @forelse($unreadNotifications as $notification)
                @php
                    $title = data_get($notification->data, 'title', 'Notifikasi');
                    $message = data_get($notification->data, 'message', '-');
                    $icon = data_get($notification->data, 'icon', 'bi-bell');
                @endphp

                <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                    @csrf
                    <button class="dropdown-item notification-item text-start" type="submit">
                        <span class="notification-icon">
                            <i class="bi {{ $icon }}"></i>
                        </span>
                        <span class="notification-content">
                            <span class="notification-title">{{ $title }}</span>
                            <span class="notification-message">{{ $message }}</span>
                            <span class="notification-time">{{ $notification->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <div class="text-center text-muted py-4 px-3">
                    <i class="bi bi-bell-slash fs-4 d-block mb-2"></i>
                    Tidak ada notifikasi baru.
                </div>
            @endforelse
        </div>
    </div>
</div>
