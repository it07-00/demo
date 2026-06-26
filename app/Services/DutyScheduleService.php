<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DutyScheduleDTO;
use App\Models\DutySchedule;
use App\Models\User;
use App\Notifications\DutyScheduleAssigned;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final class DutyScheduleService
{
    /**
     * @return Collection<int, DutySchedule>
     */
    public function getEventsInRange(string $start, string $end, ?int $filterUserId = null): Collection
    {
        return DutySchedule::query()
            ->with(['creator:id,name', 'users:id,name'])
            ->where(static function ($query) use ($start, $end): void {
                $query->whereBetween('start_at', [$start, $end])
                    ->orWhereBetween('end_at', [$start, $end])
                    ->orWhere(static function ($query) use ($start, $end): void {
                        $query->where('start_at', '<=', $start)
                            ->where('end_at', '>=', $end);
                    });
            })
            ->when($filterUserId, static function ($query, $userId): void {
                $query->where(static function ($q) use ($userId): void {
                    $q->where('created_by', $userId)
                      ->orWhereHas('users', static function ($q2) use ($userId): void {
                          $q2->where('users.id', $userId);
                      });
                });
            })
            ->get();
    }

    public function create(DutyScheduleDTO $dto): DutySchedule
    {
        $schedule = \Illuminate\Support\Facades\DB::transaction(function () use ($dto): DutySchedule {
            $schedule = DutySchedule::create([
                'title' => $dto->title,
                'description' => $dto->description,
                'location' => $dto->location,
                'start_at' => $dto->start_at,
                'end_at' => $dto->end_at,
                'label_color' => $dto->label_color,
                'is_private' => $dto->is_private,
                'created_by' => $dto->created_by ?? auth()->id(),
            ]);

            $schedule->users()->sync($dto->userIds);

            return $schedule;
        });

        $this->notifyAssignedUsers($schedule, $dto->userIds);

        return $schedule;
    }

    public function update(DutySchedule $schedule, DutyScheduleDTO $dto): DutySchedule
    {
        $previousUserIds = $schedule->users()->pluck('users.id')->toArray();

        $schedule = \Illuminate\Support\Facades\DB::transaction(function () use ($schedule, $dto): DutySchedule {
            $schedule->update([
                'title' => $dto->title,
                'description' => $dto->description,
                'location' => $dto->location,
                'start_at' => $dto->start_at,
                'end_at' => $dto->end_at,
                'label_color' => $dto->label_color,
                'is_private' => $dto->is_private,
            ]);

            $schedule->users()->sync($dto->userIds);

            return $schedule->refresh();
        });

        // Only notify newly assigned users
        $newUserIds = array_diff($dto->userIds, $previousUserIds);
        if (!empty($newUserIds)) {
            $this->notifyAssignedUsers($schedule, $newUserIds);
        }

        return $schedule;
    }

    public function delete(DutySchedule $schedule): void
    {
        $schedule->delete();
    }

    /**
     * Notify assigned users about a duty schedule (excluding the creator).
     *
     * @param array<int> $userIds
     */
    private function notifyAssignedUsers(DutySchedule $schedule, array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $creatorId = $schedule->created_by;
        $creatorName = $schedule->creator?->name ?? auth()->user()?->name ?? 'N/A';

        $usersToNotify = User::whereIn('id', $userIds)
            ->where('id', '!=', $creatorId)
            ->get();

        if ($usersToNotify->isNotEmpty()) {
            Notification::send($usersToNotify, new DutyScheduleAssigned($schedule, $creatorName));
        }
    }
}
