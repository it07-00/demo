<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\DutySchedule;
use App\Models\User;

final class DutySchedulePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::ScheduleView->value);
    }

    public function view(User $user, DutySchedule $schedule): bool
    {
        return $user->hasPermissionTo(PermissionEnum::ScheduleView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::ScheduleCreate->value);
    }

    public function update(User $user, DutySchedule $schedule): bool
    {
        return $user->hasPermissionTo(PermissionEnum::ScheduleUpdate->value) &&
            $schedule->created_by === $user->id;
    }

    public function delete(User $user, DutySchedule $schedule): bool
    {
        return $user->hasPermissionTo(PermissionEnum::ScheduleDelete->value) &&
            $schedule->created_by === $user->id;
    }
}
