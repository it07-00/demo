<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;

final readonly class DailyReportDTO
{
    public function __construct(
        public int $userId,
        public string $reportDate,
        public string $workDone,
        public ?string $planTomorrow,
        public ?string $issues,
    ) {}

    /**
     * @param array{
     *     user_id: int,
     *     report_date: string,
     *     work_done: string,
     *     plan_tomorrow?: string|null,
     *     issues?: string|null,
     * } $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            reportDate: Carbon::parse($data['report_date'])->toDateString(),
            workDone: trim($data['work_done']),
            planTomorrow: isset($data['plan_tomorrow']) && filled($data['plan_tomorrow'])
                ? trim($data['plan_tomorrow'])
                : null,
            issues: isset($data['issues']) && filled($data['issues'])
                ? trim($data['issues'])
                : null,
        );
    }
}
