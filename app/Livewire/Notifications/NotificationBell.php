<?php

declare(strict_types=1);

namespace App\Livewire\Notifications;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

final class NotificationBell extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->loadUnreadCount();
    }

    public function loadUnreadCount(): void
    {
        $this->unreadCount = Auth::user()?->unreadNotifications()->count() ?? 0;
    }

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()?->notifications()->find($id);

        if ($notification) {
            $notification->markAsRead();
            $this->loadUnreadCount();
        }

        // Redirect to the notification URL
        $url = $notification?->data['url'] ?? null;
        if ($url) {
            $this->redirect($url);
        }
    }

    public function markAllAsRead(): void
    {
        Auth::user()?->unreadNotifications->markAsRead();
        $this->unreadCount = 0;
    }

    public function deleteNotification(string $id): void
    {
        Auth::user()?->notifications()->where('id', $id)->delete();
        $this->loadUnreadCount();
    }

    public function render(): View
    {
        $notifications = Auth::user()
            ?->notifications()
            ->latest()
            ->take(20)
            ->get() ?? collect();

        return view('livewire.notifications.notification-bell', [
            'notifications' => $notifications,
        ]);
    }
}
