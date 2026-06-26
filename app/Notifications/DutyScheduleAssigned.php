<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\DutySchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

final class DutyScheduleAssigned extends Notification
{
    use Queueable;

    public function __construct(
        private readonly DutySchedule $schedule,
        private readonly string $creatorName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Phân công lịch công tác',
            'message' => 'Bạn được phân công lịch: '.$this->schedule->title,
            'schedule_id' => $this->schedule->id,
            'schedule_title' => $this->schedule->title,
            'creator_name' => $this->creatorName,
            'icon' => 'fi-rr-calendar',
            'url' => '/duty-schedules',
        ];
    }
}
