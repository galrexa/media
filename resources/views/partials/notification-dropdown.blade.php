<!-- resources/views/layouts/partials/notification-dropdown.blade.php -->
<li class="nav-item dropdown notification-dropdown">
    <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-bell"></i>
        @if(isset($unreadNotificationCount) && $unreadNotificationCount > 0)
            <span class="badge bg-danger notification-badge">{{ $unreadNotificationCount }}</span>
        @endif
    </a>
    <ul class="dropdown-menu dropdown-menu-end notification-dropdown-menu" aria-labelledby="notificationDropdown">
        <li class="dropdown-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Notifikasi</h6>
            @if(isset($unreadNotificationCount) && $unreadNotificationCount > 0)
                <span class="badge bg-primary">{{ $unreadNotificationCount }} baru</span>
            @endif
        </li>

        <div class="notification-list" id="notification-list">
            @if(isset($notifications) && count($notifications) > 0)
                @foreach($notifications as $notification)
                    <li class="notification-item">
                        <a href="{{ route('notifikasi.markAsRead', $notification) }}" class="dropdown-item {{ $notification->is_read ? '' : 'unread' }}">
                            <div class="d-flex">
                                <div class="notification-icon
                                    @if($notification->tipe == 'verifikasi')
                                        bg-primary
                                    @elseif($notification->tipe == 'tolak')
                                        bg-danger
                                    @elseif($notification->tipe == 'publikasi')
                                        bg-success
                                    @else
                                        bg-info
                                    @endif
                                ">
                                    @if($notification->tipe == 'verifikasi')
                                        <i class="bi bi-clipboard-check"></i>
                                    @elseif($notification->tipe == 'tolak')
                                        <i class="bi bi-x-circle"></i>
                                    @elseif($notification->tipe == 'publikasi')
                                        <i class="bi bi-check-circle"></i>
                                    @else
                                        <i class="bi bi-info-circle"></i>
                                    @endif
                                </div>
                                <div class="notification-content">
                                    <h6 class="notification-title">{{ $notification->judul }}</h6>
                                    <p class="notification-text">{{ Str::limit($notification->pesan, 60) }}</p>
                                    <small class="notification-time">{{ $notification->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                @endforeach
            @else
                <li class="empty-notification">
                    <p class="text-center py-3 text-muted">Tidak ada notifikasi</p>
                </li>
            @endif
        </div>

        <li><hr class="dropdown-divider"></li>
        <li class="dropdown-footer">
            <div class="d-flex justify-content-between">
                <a href="{{ route('notifikasi.index') }}" class="text-primary small">Lihat Semua</a>
                @if(isset($unreadNotificationCount) && $unreadNotificationCount > 0)
                    <a href="{{ route('notifikasi.markAllAsRead') }}" class="text-muted small">Tandai Semua Dibaca</a>
                @endif
            </div>
        </li>
    </ul>
</li>

<style>
/* Styling untuk notification dropdown */
.notification-dropdown .notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    font-size: 0.65rem;
    padding: 0.2rem 0.35rem;
    transform: translate(25%, -25%);
}

.notification-dropdown-menu {
    width: 320px;
    padding: 0;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.dropdown-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    padding: 0.75rem 1rem;
}

.notification-list {
    max-height: 350px;
    overflow-y: auto;
}

.notification-item {
    border-bottom: 1px solid #f1f1f1;
}

.notification-item .dropdown-item {
    padding: 0.75rem 1rem;
    white-space: normal;
}

.notification-item .dropdown-item:active,
.notification-item .dropdown-item:focus {
    background-color: rgba(13, 110, 253, 0.05);
}

.notification-item .dropdown-item.unread {
    background-color: rgba(13, 110, 253, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
}

.notification-icon i {
    color: white;
    font-size: 1.2rem;
}

.notification-content {
    flex-grow: 1;
    overflow: hidden;
}

.notification-title {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
}

.notification-text {
    margin: 0.25rem 0;
    font-size: 0.8rem;
    color: #666;
}

.notification-time {
    color: #999;
    font-size: 0.75rem;
}

.dropdown-footer {
    background-color: #f8f9fa;
    padding: 0.5rem 1rem;
    border-top: 1px solid #e9ecef;
}

.empty-notification {
    padding: 1rem;
}
</style>
