<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\DailyProgressEntry;
use App\Models\OperationCrmCustomer;
use App\Models\OperationProject;
use App\Models\OperationReceivable;
use App\Models\OperationRecruitmentReport;
use App\Models\OperationResponsibility;
use App\Models\User;
use App\Models\WeeklyTarget;
use App\Models\WeeklyTargetAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class GoogleSheetSeeder extends Seeder
{
    private const array CRM_STAGES = [
        'Đang trao đổi',
        'Ăn cafe',
        'Ký hợp đồng dài hạn',
        'Duy trì & mở rộng',
    ];

    private const array CRM_RELATIONSHIPS = [
        'Rất tốt',
        'Tốt',
        'Bình thường',
        'Cần chăm sóc',
    ];

    public function run(): void
    {
        // 1. Clear existing operational/KPI data to start fresh
        Schema::disableForeignKeyConstraints();
        OperationRecruitmentReport::query()->truncate();
        DailyProgressEntry::query()->truncate();
        WeeklyTargetAssignment::query()->truncate();
        WeeklyTarget::query()->truncate();
        OperationProject::query()->truncate();
        OperationReceivable::query()->truncate();
        OperationCrmCustomer::query()->truncate();
        OperationResponsibility::query()->truncate();
        Schema::enableForeignKeyConstraints();

        // Seed responsibilities (needed for project checklist / progress)
        $this->seedResponsibilities();

        // 2. Read and parse the CSV file
        $csvPath = database_path('seeders/kpi_data.csv');
        if (! file_exists($csvPath)) {
            $this->command->error("CSV file not found at: {$csvPath}");
            return;
        }

        $file = fopen($csvPath, 'r');
        $headers = fgetcsv($file); // skip header row

        $rows = [];
        while (($row = fgetcsv($file)) !== false) {
            // Header mapping:
            // 0: Mã (KPI0001)
            // 1: Tuần (2026-W14)
            // 2: Chi nhánh (Đà Nẵng)
            // 3: Dự án (CFVN)
            // 4: Quản lý (Ngô Trọng Tài)
            // 5: Chuyên viên (Võ Tấn Minh / empty)
            // 6: KPI giao (100)
            // 7: Đăng ký (6)
            // 8: Phỏng vấn (6)
            // 9: Đỗ PV (5)
            // 10: Đi làm (4)
            if (empty($row[1]) || empty($row[3])) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($file);

        $this->command->info("Parsed " . count($rows) . " rows from CSV.");

        // 3. Ensure all users (Managers & Specialists) exist
        $usersByName = $this->seedUsers($rows);

        // 4. Create projects
        $projectsByUniqueKey = $this->seedProjects($rows, $usersByName);

        // 5. Seed Weekly Targets, Assignments, and daily logs / reports
        $this->seedKpisAndDailyReports($rows, $usersByName, $projectsByUniqueKey);

        // 6. Seed CRM Customers and Receivables based on unique customers/projects
        $this->seedCrmAndReceivables($projectsByUniqueKey);

        $this->command->info("Successfully seeded Google Sheet database data!");
    }

    private function seedResponsibilities(): void
    {
        $responsibilities = [
            ['no' => 1, 'phase' => 'Khởi động', 'name' => 'Khảo sát nhu cầu khách hàng'],
            ['no' => 2, 'phase' => 'Khởi động', 'name' => 'Ký hợp đồng dịch vụ'],
            ['no' => 3, 'phase' => 'Khởi động', 'name' => 'Lập kế hoạch nhân sự'],
            ['no' => 4, 'phase' => 'Khởi động', 'name' => 'Tuyển dụng lao động'],
            ['no' => 5, 'phase' => 'Khởi động', 'name' => 'Đào tạo định hướng'],
            ['no' => 6, 'phase' => 'Khởi động', 'name' => 'Bố trí chỗ ở / ký túc xá'],
            ['no' => 7, 'phase' => 'Khởi động', 'name' => 'Làm thủ tục nhập việc'],
            ['no' => 8, 'phase' => 'Vận hành', 'name' => 'Phân ca làm việc'],
            ['no' => 9, 'phase' => 'Vận hành', 'name' => 'Chấm công hàng ngày'],
            ['no' => 10, 'phase' => 'Vận hành', 'name' => 'Quản lý chuyên cần'],
            ['no' => 11, 'phase' => 'Vận hành', 'name' => 'Xử lý lao động vắng / nghỉ'],
            ['no' => 12, 'phase' => 'Vận hành', 'name' => 'Bổ sung lao động thay thế'],
            ['no' => 13, 'phase' => 'Vận hành', 'name' => 'Giải quyết khiếu nại lao động'],
            ['no' => 14, 'phase' => 'Vận hành', 'name' => 'Theo dõi an toàn lao động'],
            ['no' => 15, 'phase' => 'Vận hành', 'name' => 'Báo cáo số liệu hàng ngày'],
            ['no' => 16, 'phase' => 'Vận hành', 'name' => 'Đối soát công với khách hàng'],
            ['no' => 17, 'phase' => 'Hoàn tất', 'name' => 'Tính lương & thanh toán'],
            ['no' => 18, 'phase' => 'Hoàn tất', 'name' => 'Nghiệm thu công với khách'],
            ['no' => 19, 'phase' => 'Hoàn tất', 'name' => 'Xuất hóa đơn / đối soát công nợ'],
            ['no' => 20, 'phase' => 'Hoàn tất', 'name' => 'Đánh giá chất lượng dịch vụ'],
            ['no' => 21, 'phase' => 'Hoàn tất', 'name' => 'Lưu hồ sơ & tổng kết dự án'],
        ];

        foreach ($responsibilities as $res) {
            OperationResponsibility::query()->create($res);
        }
    }

    private function seedUsers(array $rows): array
    {
        $usersByName = [];

        // Ensure default users
        $defaultUsers = [
            'giamdoc' => ['name' => 'Nguyễn Giám Đốc', 'email' => 'giamdoc@example.com', 'role' => RoleEnum::Director->value],
            'it' => ['name' => 'Trần Kỹ Thuật (IT)', 'email' => 'it@example.com', 'role' => RoleEnum::IT->value],
            'sales' => ['name' => 'Lê Kinh Doanh', 'email' => 'sales@example.com', 'role' => RoleEnum::Sales->value],
            'ketoan' => ['name' => 'Phạm Kế Toán', 'email' => 'ketoan@example.com', 'role' => RoleEnum::Accountant->value],
            'tuvan' => ['name' => 'Hoàng Tư Vấn', 'email' => 'tuvan@example.com', 'role' => RoleEnum::Consultant->value],
        ];

        foreach ($defaultUsers as $username => $data) {
            $user = User::query()->firstOrCreate(
                ['username' => $username],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                ]
            );
            $user->syncRoles([$data['role']]);
            $usersByName[$data['name']] = $user;
        }

        // Add Super Admin to usersByName map if exists
        $superAdmin = User::query()->where('username', 'superadmin')->first();
        if ($superAdmin) {
            $usersByName['Super Admin'] = $superAdmin;
        }

        foreach ($rows as $row) {
            $branch = trim($row[2]);
            $managerName = $this->normalizeName($row[4]);
            $specialistName = $this->normalizeName($row[5]);

            if (! empty($managerName) && ! isset($usersByName[$managerName])) {
                $username = $this->usernameFor($managerName);
                $user = User::query()->updateOrCreate(
                    ['username' => $username],
                    [
                        'name' => $managerName,
                        'email' => $username.'@ttvh-thanhcong.local',
                        'password' => Hash::make('password'),
                        'operation_branch' => $branch,
                        'operation_role' => 'Quản lý vận hành',
                        'employment_status' => 'Chính thức',
                    ]
                );
                $user->syncRoles([RoleEnum::Director->value]);
                $usersByName[$managerName] = $user;
            }

            if (! empty($specialistName) && ! isset($usersByName[$specialistName])) {
                $username = $this->usernameFor($specialistName);
                $user = User::query()->updateOrCreate(
                    ['username' => $username],
                    [
                        'name' => $specialistName,
                        'email' => $username.'@ttvh-thanhcong.local',
                        'password' => Hash::make('password'),
                        'operation_branch' => $branch,
                        'operation_role' => 'Chuyên viên vận hành',
                        'employment_status' => 'Chính thức',
                    ]
                );
                $user->syncRoles([RoleEnum::Consultant->value]);
                $usersByName[$specialistName] = $user;
            }
        }

        return $usersByName;
    }

    private function seedProjects(array $rows, array $usersByName): array
    {
        $projectsByUniqueKey = [];
        $globalIndex = 0;

        // Group rows by Branch & Project name to create unique project records
        foreach ($rows as $row) {
            $branch = trim($row[2]);
            $projectName = trim($row[3]);
            $managerName = $this->normalizeName($row[4]);
            $specialistName = $this->normalizeName($row[5]);

            $uniqueKey = "{$projectName} - {$branch}";

            if (isset($projectsByUniqueKey[$uniqueKey])) {
                // If team member is not in team list, add them
                if (! empty($specialistName)) {
                    $team = $projectsByUniqueKey[$uniqueKey]->team ?? [];
                    if (! in_array($specialistName, $team, true)) {
                        $team[] = $specialistName;
                        $projectsByUniqueKey[$uniqueKey]->update(['team' => $team]);
                    }
                }
                continue;
            }

            $globalIndex++;
            $code = 'DA-' . str_pad((string) $globalIndex, 3, '0', STR_PAD_LEFT);
            $externalId = 'P' . str_pad((string) $globalIndex, 3, '0', STR_PAD_LEFT);

            $manager = $usersByName[$managerName] ?? null;
            $managerId = $manager ? 'M' . $manager->id : 'M0';

            $team = ! empty($specialistName) ? [$specialistName] : [];

            $isKey = in_array($projectName, ['Goertek', 'Canon', 'Luxshare', 'Foxconn', 'Samsung SEV', 'Pegatron', 'BYD', 'HI-P', 'Compal', 'Fukang', 'Amkor', 'JinkoSolar'], true);

            $model = OperationProject::query()->create([
                'external_id' => $externalId,
                'code' => $code,
                'name' => "{$projectName} - {$branch} (Đợt 1)",
                'customer' => $projectName,
                'customer_type' => $isKey ? 'Trọng điểm' : 'Thông thường',
                'branch' => $branch,
                'product' => 'Cung ứng LĐ thời vụ',
                'method' => 'Tự tuyển',
                'policy' => 'Lương cứng + tăng ca',
                'unit_price' => 30,
                'recruit_status' => 'Đang tuyển',
                'manager_external_id' => $managerId,
                'manager_name' => $managerName ?: 'Chưa phân công',
                'unassigned' => empty($managerName),
                'team' => $team,
                'status' => 'Đang vận hành',
                'demand' => 100,
                'actual' => 0,
                'shortage' => 100,
                'progress' => 10,
                'contract_start' => '2026-01-01',
                'contract_end' => '2026-12-31',
                'paused_days' => 0,
                'reported_today' => false,
                'docs' => [
                    ['name' => 'Hợp đồng dịch vụ.pdf', 'type' => 'Hợp đồng'],
                    ['name' => 'Bảng lương T5-2026.xlsx', 'type' => 'Bảng lương'],
                ],
            ]);

            $projectsByUniqueKey[$uniqueKey] = $model;
        }

        return $projectsByUniqueKey;
    }

    private function seedKpisAndDailyReports(array $rows, array $usersByName, array $projectsByUniqueKey): void
    {
        $creator = User::query()->where('username', 'superadmin')->first()
            ?? User::query()->first();

        // Pre-group rows by WeeklyTarget to sum total customer_demand and manager_accepted
        $weeklyTargetData = [];

        foreach ($rows as $row) {
            $weekStr = trim($row[1]); // 2026-W14
            $branch = trim($row[2]);
            $projectName = trim($row[3]);
            $kpiGiao = (int) $row[6];

            $uniqueKey = "{$projectName} - {$branch}";
            $project = $projectsByUniqueKey[$uniqueKey] ?? null;
            if (! $project) {
                continue;
            }

            [$year, $weekNumber] = $this->parseWeek($weekStr);

            $wtKey = "{$project->id}_{$year}_{$weekNumber}";

            if (! isset($weeklyTargetData[$wtKey])) {
                $weeklyTargetData[$wtKey] = [
                    'project' => $project,
                    'year' => $year,
                    'week_number' => $weekNumber,
                    'week_start' => $this->getWeekStartDate($year, $weekNumber),
                    'week_end' => $this->getWeekStartDate($year, $weekNumber)->addDays(6),
                    'kpi_sum' => 0,
                ];
            }

            $weeklyTargetData[$wtKey]['kpi_sum'] += $kpiGiao;
        }

        // Create Weekly Targets
        $weeklyTargetsMap = [];
        foreach ($weeklyTargetData as $wtKey => $data) {
            $target = WeeklyTarget::query()->create([
                'operation_project_id' => $data['project']->id,
                'year' => $data['year'],
                'week_number' => $data['week_number'],
                'week_start' => $data['week_start']->toDateString(),
                'week_end' => $data['week_end']->toDateString(),
                'customer_demand' => $data['kpi_sum'] ?: 100,
                'manager_accepted' => $data['kpi_sum'] ?: 100,
                'created_by' => $creator->id,
            ]);
            $weeklyTargetsMap[$wtKey] = $target;
        }

        // Record project aggregates to update demand/actual at the end
        $projectActuals = [];

        // Create Assignments and distribute daily entries / recruitment reports
        foreach ($rows as $row) {
            $weekStr = trim($row[1]);
            $branch = trim($row[2]);
            $projectName = trim($row[3]);
            $managerName = $this->normalizeName($row[4]);
            $specialistName = $this->normalizeName($row[5]);
            $kpiGiao = (int) $row[6];
            $registered = (int) $row[7];
            $interviewed = (int) $row[8];
            $passed = (int) $row[9];
            $started = (int) $row[10];

            $uniqueKey = "{$projectName} - {$branch}";
            $project = $projectsByUniqueKey[$uniqueKey] ?? null;
            if (! $project) {
                continue;
            }

            [$year, $weekNumber] = $this->parseWeek($weekStr);
            $wtKey = "{$project->id}_{$year}_{$weekNumber}";
            $target = $weeklyTargetsMap[$wtKey] ?? null;
            if (! $target) {
                continue;
            }

            $specialist = $usersByName[$specialistName] ?? null;
            $manager = $usersByName[$managerName] ?? null;

            // Skip empty rows with no assignments/metrics
            if (! $specialist && $kpiGiao === 0 && $registered === 0 && $started === 0) {
                continue;
            }

            // Create assignment if specialist exists
            $assignment = null;
            if ($specialist) {
                $assignment = WeeklyTargetAssignment::query()->create([
                    'weekly_target_id' => $target->id,
                    'user_id' => $specialist->id,
                    'assigned_quantity' => $kpiGiao,
                    'assigned_by' => $manager ? $manager->id : $creator->id,
                ]);
            }

            // Distribute cumulative weekly stats into daily reports (Monday to Friday)
            $weekStart = CarbonImmutable::instance($target->week_start);
            
            // Distribute values:
            $distRegistered = $this->distributeValue($registered, 5);
            $distInterviewed = $this->distributeValue($interviewed, 5);
            $distPassed = $this->distributeValue($passed, 5);
            $distStarted = $this->distributeValue($started, 5);

            for ($dayIdx = 0; $dayIdx < 5; $dayIdx++) {
                $date = $weekStart->addDays($dayIdx);
                $dailyStarted = $distStarted[$dayIdx] ?? 0;
                $dailyRegistered = $distRegistered[$dayIdx] ?? 0;
                $dailyInterviewed = $distInterviewed[$dayIdx] ?? 0;
                $dailyPassed = $distPassed[$dayIdx] ?? 0;

                // 1. Create DailyProgressEntry for Work Progress
                if ($assignment && $dailyStarted > 0) {
                    DailyProgressEntry::query()->create([
                        'weekly_target_assignment_id' => $assignment->id,
                        'entry_date' => $date->toDateString(),
                        'achieved' => $dailyStarted,
                        'note' => 'Cập nhật tiến độ tự động',
                        'created_by' => $specialist->id,
                    ]);
                }

                // 2. Accumulate values for OperationRecruitmentReport (grouped by project and date)
                if ($dailyRegistered > 0 || $dailyStarted > 0 || $dailyInterviewed > 0 || $dailyPassed > 0) {
                    $reportKey = "{$project->id}_{$date->toDateString()}";

                    if (! isset($projectActuals[$project->id])) {
                        $projectActuals[$project->id] = 0;
                    }
                    $projectActuals[$project->id] += $dailyStarted;

                    $report = OperationRecruitmentReport::query()->where([
                        'operation_project_id' => $project->id,
                        'report_date' => $date->toDateString(),
                    ])->first();

                    if ($report) {
                        $report->increment('registered', $dailyRegistered);
                        $report->increment('interviewed', $dailyInterviewed);
                        $report->increment('passed', $dailyPassed);
                        $report->increment('started', $dailyStarted);
                    } else {
                        OperationRecruitmentReport::query()->create([
                            'operation_project_id' => $project->id,
                            'report_date' => $date->toDateString(),
                            'branch' => $project->branch,
                            'customer' => $project->customer,
                            'manager' => $project->manager_name,
                            'demand' => $project->demand,
                            'method' => $project->method,
                            'registered' => $dailyRegistered,
                            'interviewed' => $dailyInterviewed,
                            'passed' => $dailyPassed,
                            'started' => $dailyStarted,
                            'partner_trial' => 0,
                            'rank' => 'A',
                            'reporter' => $specialistName ?: ($managerName ?: 'Hệ thống'),
                            'reported_at' => '17:30',
                            'issues' => null,
                            'approved' => true,
                        ]);
                    }
                }
            }
        }

        // Update Project Actuals & Shortages based on cumulative achievements
        foreach ($projectsByUniqueKey as $project) {
            $actual = $projectActuals[$project->id] ?? 0;
            $project->update([
                'actual' => $actual,
                'shortage' => max(0, $project->demand - $actual),
                'reported_today' => true,
            ]);
        }
    }

    private function seedCrmAndReceivables(array $projectsByUniqueKey): void
    {
        $uniqueCustomers = [];
        foreach ($projectsByUniqueKey as $project) {
            if (! in_array($project->customer, $uniqueCustomers, true)) {
                $uniqueCustomers[] = $project->customer;
            }
        }

        $today = CarbonImmutable::instance(now()->startOfDay());

        foreach ($uniqueCustomers as $index => $customerName) {
            // Seed CRM Customer
            $stageIdx = $index % count(self::CRM_STAGES);
            $relIdx = $index % count(self::CRM_RELATIONSHIPS);

            OperationCrmCustomer::query()->create([
                'name' => $customerName,
                'type' => 'Trọng điểm',
                'stage' => self::CRM_STAGES[$stageIdx],
                'stage_idx' => $stageIdx,
                'relationship' => self::CRM_RELATIONSHIPS[$relIdx],
                'contact_name' => 'Liên hệ ' . $customerName,
                'contact_role' => 'Phụ trách nhân sự',
                'revenue_monthly' => 100 + ($index * 15),
                'last_meeting' => $today->subDays(5 + $index)->toDateString(),
                'next_meeting' => $today->addDays(5 + $index)->toDateString(),
                'notes' => [
                    'Khách hàng hài lòng với chất lượng cung cấp.',
                    'Đang đàm phán mở rộng quy mô hợp đồng.',
                ],
            ]);

            // Seed Receivable
            OperationReceivable::query()->create([
                'external_id' => 'CN' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'customer' => $customerName,
                'amount' => 30 + ($index * 12),
                'due_date' => $today->addDays(($index % 2 === 0 ? 5 : -3) + $index)->toDateString(),
                'state' => ($index % 2 === 0) ? 'Trong hạn' : 'Quá hạn',
                'note' => 'Thanh toán phí dịch vụ đợt ' . ($index + 1),
                'paid' => false,
            ]);
        }
    }

    private function parseWeek(string $weekStr): array
    {
        // Format: "2026-W14"
        $parts = explode('-W', $weekStr);
        $year = (int) ($parts[0] ?? 2026);
        $weekNumber = (int) ($parts[1] ?? 1);
        return [$year, $weekNumber];
    }

    private function getWeekStartDate(int $year, int $weekNumber): CarbonImmutable
    {
        $dto = new \DateTime();
        $dto->setISODate($year, $weekNumber);
        return CarbonImmutable::instance($dto->setTime(0, 0));
    }

    private function distributeValue(int $total, int $days): array
    {
        if ($total <= 0) {
            return array_fill(0, $days, 0);
        }

        $base = (int) floor($total / $days);
        $remainder = $total - ($base * $days);

        $dist = [];
        for ($i = 0; $i < $days; $i++) {
            $dist[] = $base + ($i < $remainder ? 1 : 0);
        }

        // Shuffle so it looks natural
        shuffle($dist);

        return $dist;
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);
        return match ($name) {
            'Phùng Minh HIếu' => 'Phùng Minh Hiếu',
            default => $name
        };
    }

    private function usernameFor(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
