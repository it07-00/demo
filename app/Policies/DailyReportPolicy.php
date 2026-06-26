<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\User;

final class DailyReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->can(PermissionEnum::ReportView->value);
    }

    public function view(User $user, DailyReport $report): bool
    {
        // Owner can always view their own
        if ($report->user_id === $user->id) {
            return true;
        }

        // Others can view only if they have report.view and are not staff (like Director)
        return $user->can(PermissionEnum::ReportView->value) && ! $user->can(PermissionEnum::ReportCreate->value);
    }

    public function create(User $user): bool
    {
        return $user->can(PermissionEnum::ReportCreate->value);
    }

    public function update(User $user, DailyReport $report): bool
    {
        return $report->user_id === $user->id;
    }

    public function delete(User $user, DailyReport $report): bool
    {
        return $report->user_id === $user->id || $user->can(PermissionEnum::ReportDelete->value);
    }
}
