<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class DashboardService
{
    /**
     * @return array<string, int>
     */
    public function stats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        return [
            'users' => User::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'schedules_today' => DutySchedule::query()
                ->whereBetween('start_at', [$todayStart, $todayEnd])
                ->count(),
            'schedules_week' => DutySchedule::query()
                ->whereBetween('start_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->count(),
            'reports_today' => DailyReport::query()
                ->whereDate('report_date', now()->toDateString())
                ->count(),
            'reports_month' => DailyReport::query()
                ->whereBetween('report_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->count(),
            'new_users_month' => User::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
        ];
    }

    /**
     * @return array{submitted: int, missing: int, percent: int}
     */
    public function reportStatus(): array
    {
        $users = User::query()->count();
        $submitted = DailyReport::query()
            ->whereDate('report_date', now()->toDateString())
            ->distinct('user_id')
            ->count('user_id');

        return [
            'submitted' => $submitted,
            'missing' => max($users - $submitted, 0),
            'percent' => $users > 0 ? (int) round(($submitted / $users) * 100) : 0,
        ];
    }

    /**
     * @return Collection<int, array{name: string, users_count: int, percent: int}>
     */
    public function roleDistribution(): Collection
    {
        $totalUsers = max(User::query()->count(), 1);

        return Role::query()
            ->withCount('users')
            ->orderByDesc('users_count')
            ->orderBy('name')
            ->take(6)
            ->get()
            ->map(static fn (Role $role): array => [
                'name' => $role->name,
                'users_count' => (int) $role->users_count,
                'percent' => (int) round(((int) $role->users_count / $totalUsers) * 100),
            ]);
    }

    /**
     * @return Collection<int, array{title: string, time: string, creator: string, location: string|null, color: string, is_private: bool}>
     */
    public function recentSchedules(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        $query = DutySchedule::query()
            ->with(['creator:id,name', 'users:id,name'])
            ->orderBy('start_at')
            ->where('start_at', '>=', now()->startOfDay());

        if (! $user->hasRole(RoleEnum::SuperAdmin->value) && ! $user->hasRole(RoleEnum::Director->value)) {
            $query->where(static function ($query) use ($user): void {
                $query->where('is_private', false)
                    ->orWhere('created_by', $user->id)
                    ->orWhereHas('users', static function ($query) use ($user): void {
                        $query->where('users.id', $user->id);
                    });
            });
        }

        return $query->take(5)->get()->map(static fn (DutySchedule $schedule): array => [
            'title' => $schedule->title,
            'time' => $schedule->start_at->format('H:i d/m'),
            'creator' => $schedule->creator?->name ?? 'N/A',
            'location' => $schedule->location ? Str::limit($schedule->location, 28) : null,
            'color' => $schedule->label_color ?: 'primary',
            'is_private' => (bool) $schedule->is_private,
        ]);
    }

    /**
     * @return Collection<int, array{user: string, date: string, summary: string}>
     */
    public function recentReports(): Collection
    {
        $user = Auth::user();

        if ($user === null) {
            return collect();
        }

        $canViewAll = $user->hasRole(RoleEnum::SuperAdmin->value)
            || ($user->can('report.view') && ! $user->can('report.create'));

        $query = DailyReport::query()
            ->with('user:id,name')
            ->orderByDesc('report_date')
            ->orderByDesc('created_at');

        if (! $canViewAll) {
            $query->where('user_id', $user->id);
        }

        return $query->take(5)->get()->map(static fn (DailyReport $report): array => [
            'user' => $report->user?->name ?? 'N/A',
            'date' => $report->report_date->format('d/m/Y'),
            'summary' => Str::limit($report->work_done, 78),
        ]);
    }
}
