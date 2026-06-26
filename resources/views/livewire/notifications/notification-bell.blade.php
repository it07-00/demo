<div wire:poll.30s="loadUnreadCount" class="notification-bell-wrapper">
    <div class="dropdown">
        <button
            class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative"
            type="button"
            id="notificationBellBtn"
            data-bs-toggle="dropdown"
            data-bs-auto-close="outside"
            aria-expanded="false"
            aria-label="Thông báo"
        >
            <i class="fi fi-rr-bell"></i>
            @if ($unreadCount > 0)
                <span class="notification-badge">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <div class="dropdown-menu dropdown-menu-end notification-dropdown p-0" aria-labelledby="notificationBellBtn">
            {{-- Header --}}
            <div class="notification-dropdown-header">
                <h6 class="mb-0 fw-bold">
                    Thông báo
                    @if ($unreadCount > 0)
                        <span class="badge bg-primary rounded-pill ms-1 fw-semibold" style="font-size: 0.65rem;">{{ $unreadCount }}</span>
                    @endif
                </h6>
                @if ($unreadCount > 0)
                    <button
                        wire:click="markAllAsRead"
                        type="button"
                        class="btn btn-link btn-sm text-decoration-none p-0"
                    >
                        <i class="fi fi-rr-check-double me-1"></i><span class="d-none d-sm-inline">Đánh dấu</span> đã đọc
                    </button>
                @endif
            </div>

            {{-- Notification List --}}
            <div class="notification-dropdown-body" data-simplebar>
                @forelse ($notifications as $notification)
                    @php
                        $data = $notification->data;
                        $isUnread = is_null($notification->read_at);
                        $iconClass = $data['icon'] ?? 'fi-rr-bell';

                        // Determine icon colors based on notification type
                        if ($isUnread) {
                            $isSchedule = str_contains($iconClass, 'calendar');
                            $iconBgClass = $isSchedule
                                ? 'bg-info-subtle text-info'
                                : 'bg-primary-subtle text-primary';
                        } else {
                            $iconBgClass = 'bg-light text-muted';
                        }
                    @endphp
                    <div class="notification-item {{ $isUnread ? 'unread' : '' }}">
                        <div class="notification-item-content"
                             wire:click="markAsRead('{{ $notification->id }}')"
                             role="button"
                             tabindex="0"
                        >
                            <div class="notification-icon-wrapper">
                                <div class="notification-icon {{ $iconBgClass }}">
                                    <i class="fi {{ $iconClass }}"></i>
                                </div>
                            </div>
                            <div class="notification-text">
                                <p class="notification-title mb-0">{{ $data['title'] ?? 'Thông báo' }}</p>
                                <p class="notification-message mb-0">{{ $data['message'] ?? '' }}</p>
                                <span class="notification-time">
                                    <i class="fi fi-rr-clock me-1"></i>{{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <button
                                wire:click="deleteNotification('{{ $notification->id }}')"
                                type="button"
                                class="btn btn-sm btn-icon text-muted"
                                title="Xóa thông báo"
                                aria-label="Xóa thông báo"
                            >
                                <i class="fi fi-rr-cross-small"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="notification-empty">
                        <div class="notification-empty-icon">
                            <i class="fi fi-rr-bell-slash"></i>
                        </div>
                        <p class="text-muted mb-0">Không có thông báo nào</p>
                        <small class="text-muted mt-1 d-block" style="font-size: 0.72rem;">
                            Thông báo sẽ xuất hiện khi có lịch trực hoặc báo cáo mới
                        </small>
                    </div>
                @endforelse
            </div>

            {{-- Footer --}}
            @if ($notifications->count() > 0)
                <div class="notification-dropdown-footer">
                    <span class="text-muted text-2xs">
                        Hiển thị {{ $notifications->count() }} thông báo gần nhất
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
