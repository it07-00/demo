<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\DailyReportDTO;
use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\User;
use App\Notifications\DailyReportSubmitted;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

final class DailyReportService
{
    /**
     * Get paginated reports, optionally filtered.
     *
     * @param int|null $userId      Filter by specific user (null = all)
     * @param string|null $date     Filter by specific date (Y-m-d)
     * @param string|null $search   Search in work_done
     * @return LengthAwarePaginator<DailyReport>
     */
    public function getReports(
        ?int $userId = null,
        ?string $date = null,
        ?string $search = null,
        int $perPage = 15,
    ): LengthAwarePaginator {
        return DailyReport::query()
            ->with('user')
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($date, fn ($q) => $q->whereDate('report_date', $date))
            ->when($search, fn ($q) => $q->where('work_done', 'like', "%{$search}%"))
            ->orderByDesc('report_date')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get reports in a date range for calendar view.
     *
     * @param string $start
     * @param string $end
     * @param int|null $userId
     * @return \Illuminate\Database\Eloquent\Collection<int, DailyReport>
     */
    public function getReportsInRange(string $start, string $end, ?int $userId = null): \Illuminate\Database\Eloquent\Collection
    {
        return DailyReport::query()
            ->with('user')
            ->whereBetween('report_date', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('report_date')
            ->get();
    }

    public function createReport(DailyReportDTO $dto): DailyReport
    {
        $report = DB::transaction(function () use ($dto): DailyReport {
            return DailyReport::create([
                'user_id'       => $dto->userId,
                'report_date'   => $dto->reportDate,
                'work_done'     => $dto->workDone,
                'plan_tomorrow' => $dto->planTomorrow,
                'issues'        => $dto->issues,
            ]);
        });

        $this->notifyDirectors($report);

        return $report;
    }

    public function updateReport(DailyReport $report, DailyReportDTO $dto): DailyReport
    {
        return DB::transaction(function () use ($report, $dto): DailyReport {
            $report->update([
                'report_date'   => $dto->reportDate,
                'work_done'     => $dto->workDone,
                'plan_tomorrow' => $dto->planTomorrow,
                'issues'        => $dto->issues,
            ]);
            return $report->refresh();
        });
    }

    public function deleteReport(DailyReport $report): void
    {
        DB::transaction(fn () => $report->delete());
    }

    /**
     * Check if a user already has a report for given date (excluding a record id).
     */
    public function existsForDate(int $userId, string $date, ?int $excludeId = null): bool
    {
        return DailyReport::query()
            ->where('user_id', $userId)
            ->whereDate('report_date', $date)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    /**
     * Notify all users with Director role about a new daily report.
     */
    private function notifyDirectors(DailyReport $report): void
    {
        $report->loadMissing('user');
        $reporterName = $report->user?->name ?? 'N/A';

        $directors = User::role(RoleEnum::Director->value)->get();

        if ($directors->isNotEmpty()) {
            Notification::send($directors, new DailyReportSubmitted($report, $reporterName));
        }
    }
}
