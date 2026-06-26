<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\OperationCrmCustomer;
use App\Models\OperationProject;
use App\Models\OperationReceivable;
use App\Models\OperationRecruitmentReport;
use App\Models\OperationResponsibility;
use App\Models\User;
use Carbon\CarbonImmutable;

final class OperationDataService
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $cache = null;

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        return $this->cache = $this->databaseSnapshot();
    }

    /**
     * @return array<string, mixed>
     */
    private function databaseSnapshot(): array
    {
        $today = CarbonImmutable::instance(now()->startOfDay());
        $status = [
            'operating' => 'Đang vận hành',
            'paused' => 'Tạm dừng',
        ];

        $staffDirectory = $this->staffDirectory();
        $branches = $this->branches($staffDirectory);
        $responsibilities = OperationResponsibility::query()
            ->orderBy('no')
            ->get()
            ->map(static fn (OperationResponsibility $responsibility): array => [
                'no' => $responsibility->no,
                'phase' => $responsibility->phase,
                'name' => $responsibility->name,
            ])
            ->all();
        $projects = $this->databaseProjects($today);
        $managers = $this->managers($staffDirectory, $projects);
        $specialists = $this->specialists($staffDirectory);
        $reportHistory = $this->databaseReportHistory($projects);
        $todayLogs = array_values(array_filter($reportHistory, static fn (array $report): bool => $report['date_key'] === $today->toDateString()));
        $receivables = $this->databaseReceivables($today);
        $crmCustomers = $this->databaseCrmCustomers($projects, $receivables);
        $dailySeries = $this->dailySeries($today);
        $monthlyGrowth = $this->monthlyGrowth();
        $staff = $this->staff($projects, $todayLogs, $managers, $specialists, $staffDirectory);
        $alerts = $this->alerts($projects, $receivables, $managers, $status, $today);
        $kpi = $this->kpiFor($projects, $status);

        $kpi['by_status'] = $this->countBy($projects, 'status');
        $kpi['by_branch'] = $this->countBy($projects, 'branch');
        $kpi['by_type'] = $this->countBy($projects, 'customer_type');
        $kpi['total_staff'] = count($staff);
        $kpi['total_managers'] = count(array_filter($managers, static fn (array $manager): bool => empty($manager['unassigned'])));
        $kpi['red_alerts'] = count(array_filter($alerts, static fn (array $alert): bool => $alert['level'] === 'red'));
        $kpi['amber_alerts'] = count(array_filter($alerts, static fn (array $alert): bool => $alert['level'] === 'amber'));
        $kpi['reported_today'] = count(array_filter($projects, static fn (array $project): bool => $project['reported_today']));
        $kpi['need_report_total'] = count(array_filter($projects, static fn (array $project): bool => $project['status'] === 'Đang vận hành'));
        $kpi['receivable_total'] = array_sum(array_column($receivables, 'amount'));
        $kpi['receivable_due_soon'] = array_sum(array_column(array_filter(
            $receivables,
            static fn (array $receivable): bool => $receivable['days_left'] <= 7
        ), 'amount'));

        return [
            'today' => $today,
            'status' => $status,
            'branches' => $branches,
            'responsibilities' => $responsibilities,
            'managers' => $managers,
            'specialists' => $specialists,
            'staff_directory' => $staffDirectory,
            'staff' => $staff,
            'projects' => $projects,
            'today_logs' => $todayLogs,
            'report_history' => $reportHistory,
            'daily_series' => $dailySeries,
            'monthly_growth' => $monthlyGrowth,
            'receivables' => $receivables,
            'crm_customers' => $crmCustomers,
            'crm_stages' => ['Đang trao đổi', 'Ăn cafe', 'Ký hợp đồng dài hạn', 'Duy trì & mở rộng'],
            'alerts' => $alerts,
            'kpi' => $kpi,
            'methods' => ['Cộng tác viên', 'Tự tuyển', 'Đối tác cung ứng', 'Quảng cáo tuyển', 'Giới thiệu nội bộ'],
            'products' => ['Cung ứng LĐ thời vụ', 'Cung ứng LĐ chính thức', 'Khoán việc dây chuyền', 'Vệ sinh công nghiệp', 'Outsourcing kho vận'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function databaseProjects(CarbonImmutable $today): array
    {
        return OperationProject::query()
            ->orderBy('code')
            ->get()
            ->map(static function (OperationProject $project) use ($today): array {
                $contractEnd = CarbonImmutable::instance($project->contract_end);
                $contractStart = CarbonImmutable::instance($project->contract_start);
                $demand = (int) $project->demand;
                $actual = (int) $project->actual;

                return [
                    'db_id' => $project->id,
                    'id' => $project->external_id,
                    'code' => $project->code,
                    'name' => $project->name,
                    'customer' => $project->customer,
                    'customer_type' => $project->customer_type,
                    'branch' => $project->branch,
                    'product' => $project->product,
                    'method' => $project->method,
                    'policy' => $project->policy,
                    'unit_price' => (int) $project->unit_price,
                    'recruit_status' => $project->recruit_status,
                    'manager_id' => $project->manager_external_id,
                    'manager_name' => $project->manager_name,
                    'unassigned' => (bool) $project->unassigned,
                    'team' => $project->team ?? [],
                    'status' => $project->status,
                    'demand' => $demand,
                    'actual' => $actual,
                    'fill_rate' => $demand > 0 ? $actual / $demand : 0,
                    'shortage' => (int) $project->shortage,
                    'progress' => (int) $project->progress,
                    'progress_pct' => (int) round(((int) $project->progress / 21) * 100),
                    'contract_start' => $contractStart,
                    'contract_end' => $contractEnd,
                    'contract_days_left' => $today->diffInDays($contractEnd, false),
                    'paused_days' => (int) $project->paused_days,
                    'reported_today' => (bool) $project->reported_today,
                    'docs' => $project->docs ?? [],
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function databaseReportHistory(array $projects): array
    {
        $projectById = collect($projects)->keyBy('id');

        return OperationRecruitmentReport::query()
            ->with('project:id,external_id,code,name')
            ->orderByDesc('report_date')
            ->orderBy('id')
            ->get()
            ->map(static function (OperationRecruitmentReport $report) use ($projectById): array {
                $project = $projectById->get($report->project?->external_id);
                $date = CarbonImmutable::instance($report->report_date);
                $registered = (int) $report->registered;
                $started = (int) $report->started;

                return [
                    'db_id' => $report->id,
                    'date' => $date,
                    'date_key' => $date->toDateString(),
                    'project_id' => $report->project?->external_id ?? '',
                    'code' => $report->project?->code ?? '',
                    'customer' => $report->customer,
                    'project' => $report->project?->name ?? (string) ($project['name'] ?? ''),
                    'branch' => $report->branch,
                    'manager' => $report->manager,
                    'demand' => (int) $report->demand,
                    'method' => $report->method,
                    'registered' => $registered,
                    'interviewed' => (int) $report->interviewed,
                    'passed' => (int) $report->passed,
                    'started' => $started,
                    'partner_trial' => (int) $report->partner_trial,
                    'conversion' => $registered > 0 ? $started / $registered : 0,
                    'rank' => $report->rank,
                    'reporter' => $report->reporter,
                    'reported_at' => $report->reported_at,
                    'approved' => (bool) $report->approved,
                    'issues' => $report->issues ?? '',
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function databaseReceivables(CarbonImmutable $today): array
    {
        return OperationReceivable::query()
            ->orderBy('due_date')
            ->get()
            ->map(static function (OperationReceivable $receivable) use ($today): array {
                $dueDate = CarbonImmutable::instance($receivable->due_date);
                $daysLeft = $today->diffInDays($dueDate, false);

                return [
                    'id' => $receivable->external_id,
                    'customer' => $receivable->customer,
                    'amount' => (int) $receivable->amount,
                    'note' => $receivable->note,
                    'due_date' => $dueDate,
                    'days_left' => $daysLeft,
                    'state' => $receivable->state,
                    'paid' => (bool) $receivable->paid,
                ];
            })
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $receivables
     * @return array<int, array<string, mixed>>
     */
    private function databaseCrmCustomers(array $projects, array $receivables): array
    {
        return OperationCrmCustomer::query()
            ->orderBy('stage_idx')
            ->orderBy('name')
            ->get()
            ->map(static function (OperationCrmCustomer $customer) use ($projects, $receivables): array {
                $customerProjects = array_values(array_filter($projects, static fn (array $project): bool => $project['customer'] === $customer->name));
                $customerReceivables = array_values(array_filter($receivables, static fn (array $receivable): bool => $receivable['customer'] === $customer->name));

                return [
                    'name' => $customer->name,
                    'type' => $customer->type,
                    'stage' => $customer->stage,
                    'stage_idx' => (int) $customer->stage_idx,
                    'relationship' => $customer->relationship,
                    'contact_name' => $customer->contact_name,
                    'contact_role' => $customer->contact_role,
                    'revenue_monthly' => (int) $customer->revenue_monthly,
                    'project_count' => count($customerProjects),
                    'branches' => array_values(array_unique(array_column($customerProjects, 'branch'))),
                    'last_meeting' => CarbonImmutable::instance($customer->last_meeting),
                    'next_meeting' => CarbonImmutable::instance($customer->next_meeting),
                    'notes' => $customer->notes ?? [],
                    'projects' => $customerProjects,
                    'receivables' => $customerReceivables,
                ];
            })
            ->all();
    }

    /**
     * @return array<int, array{name: string, branch_raw: string, branch: string, role: string, status: string}>
     */
    public function generatedSnapshot(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::instance(now()->startOfDay());
        $status = [
            'operating' => 'Đang vận hành',
            'paused' => 'Tạm dừng',
        ];

        $staffDirectory = $this->staffDirectory();
        $branches = $this->branches($staffDirectory);
        $responsibilities = $this->responsibilities();
        $managers = $this->managers($staffDirectory);
        $specialists = $this->specialists($staffDirectory);
        $projects = $this->projects($today, $status, $branches, $managers, $specialists);
        $todayLogs = $this->todayLogs($projects, $today);
        $reportHistory = $this->reportHistory($projects, $today);
        $receivables = $this->receivables($today);
        $crmCustomers = $this->crmCustomers($projects, $receivables, $today);
        $dailySeries = $this->dailySeries($today);
        $monthlyGrowth = $this->monthlyGrowth();
        $staff = $this->staff($projects, $todayLogs, $managers, $specialists, $staffDirectory);
        $alerts = $this->alerts($projects, $receivables, $managers, $status, $today);
        $kpi = $this->kpiFor($projects, $status);

        $kpi['by_status'] = $this->countBy($projects, 'status');
        $kpi['by_branch'] = $this->countBy($projects, 'branch');
        $kpi['by_type'] = $this->countBy($projects, 'customer_type');
        $kpi['total_staff'] = count($staff);
        $kpi['total_managers'] = count(array_filter($managers, static fn (array $manager): bool => empty($manager['unassigned'])));
        $kpi['red_alerts'] = count(array_filter($alerts, static fn (array $alert): bool => $alert['level'] === 'red'));
        $kpi['amber_alerts'] = count(array_filter($alerts, static fn (array $alert): bool => $alert['level'] === 'amber'));
        $kpi['reported_today'] = count(array_filter($projects, static fn (array $project): bool => $project['reported_today']));
        $kpi['need_report_total'] = count(array_filter($projects, static fn (array $project): bool => $project['status'] === 'Đang vận hành'));
        $kpi['receivable_total'] = array_sum(array_column($receivables, 'amount'));
        $kpi['receivable_due_soon'] = array_sum(array_column(array_filter(
            $receivables,
            static fn (array $receivable): bool => $receivable['days_left'] <= 7
        ), 'amount'));

        return [
            'today' => $today,
            'status' => $status,
            'branches' => $branches,
            'responsibilities' => $responsibilities,
            'managers' => $managers,
            'specialists' => $specialists,
            'staff_directory' => $staffDirectory,
            'staff' => $staff,
            'projects' => $projects,
            'today_logs' => $todayLogs,
            'report_history' => $reportHistory,
            'daily_series' => $dailySeries,
            'monthly_growth' => $monthlyGrowth,
            'receivables' => $receivables,
            'crm_customers' => $crmCustomers,
            'crm_stages' => ['Đang trao đổi', 'Ăn cafe', 'Ký hợp đồng dài hạn', 'Duy trì & mở rộng'],
            'alerts' => $alerts,
            'kpi' => $kpi,
            'methods' => ['Cộng tác viên', 'Tự tuyển', 'Đối tác cung ứng', 'Quảng cáo tuyển', 'Giới thiệu nội bộ'],
            'products' => ['Cung ứng LĐ thời vụ', 'Cung ứng LĐ chính thức', 'Khoán việc dây chuyền', 'Vệ sinh công nghiệp', 'Outsourcing kho vận'],
        ];
    }

    private function staffDirectory(): array
    {
        $seededStaff = User::query()
            ->whereNotNull('operation_role')
            ->orderByRaw("operation_role = 'Quản lý vận hành' desc")
            ->orderBy('operation_branch')
            ->orderBy('name')
            ->get(['name', 'operation_branch', 'operation_role', 'employment_status'])
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'branch_raw' => (string) $user->operation_branch,
                'branch' => (string) $user->operation_branch,
                'role' => (string) $user->operation_role,
                'status' => (string) $user->employment_status,
            ])
            ->values()
            ->all();

        if ($seededStaff !== []) {
            return $seededStaff;
        }

        $rows = [
            ['Đàm Ngọc Anh', 'Chi nhánh Bắc Ninh', 'Quản lý vận hành', 'Chính thức'],
            ['Hoàng Tuấn Bảo', 'Chi nhánh Bắc Ninh', 'Chuyên viên vận hành', 'Chính thức'],
            ['Hoàng Đình Anh', 'Chi nhánh Bắc Ninh', 'Chuyên viên vận hành', 'Chính thức'],
            ['Lý Văn Quyết', 'Chi nhánh Bắc Ninh', 'Chuyên viên vận hành', 'Thử việc'],
            ['Phùng Minh Hiếu', 'Chi nhánh Bắc Ninh', 'Chuyên viên vận hành', 'Chính thức'],
            ['Trần Thu Thủy', 'Chi nhánh Bắc Ninh', 'Chuyên viên vận hành', 'Chính thức'],
            ['Quách Hồng Ánh', 'Chi nhánh Bắc Giang', 'Quản lý vận hành', 'Chính thức'],
            ['Nguyễn Thạc Hùng', 'Chi nhánh Bắc Giang', 'Chuyên viên vận hành', 'Chính thức'],
            ['Nguyễn Văn Anh', 'Chi nhánh Bắc Giang', 'Chuyên viên vận hành', 'Chính thức'],
            ['Phan Văn Khánh', 'Chi nhánh Bắc Giang', 'Chuyên viên vận hành', 'Chính thức'],
            ['Lê Thị Thùy Vân', 'Hà Nam - Nam Định', 'Quản lý vận hành', 'Chính thức'],
            ['Nguyễn Huyền My', 'Chi nhánh Nam Định', 'Chuyên viên vận hành', 'Chính thức'],
            ['Ngô Trọng Tài', 'Chi nhánh Đà Nẵng', 'Quản lý vận hành', 'Chính thức'],
            ['Võ Tấn Minh', 'Chi nhánh Đà Nẵng', 'Chuyên viên vận hành', 'Chính thức'],
            ['Nguyễn Thị Thơm', 'Chi nhánh Hà Nam', 'Chuyên viên vận hành', 'Chính thức'],
            ['Vũ Thị Diệu Thúy', 'Chi nhánh Hà Nam', 'Chuyên viên vận hành', 'Chính thức'],
            ['Nguyễn Thị Thúy Vân', 'Chi nhánh Nghệ An', 'Chuyên viên vận hành', 'Thử việc'],
        ];

        return array_map(fn (array $row): array => [
            'name' => $row[0],
            'branch_raw' => $row[1],
            'branch' => $this->normalizeBranch($row[1]),
            'role' => $row[2],
            'status' => $row[3],
        ], $rows);
    }

    /**
     * @param  array<int, array<string, string>>  $staffDirectory
     * @return list<string>
     */
    private function branches(array $staffDirectory): array
    {
        $branches = [];

        foreach ($staffDirectory as $staff) {
            foreach (explode(' - ', $staff['branch']) as $branch) {
                $branch = trim($branch);

                if ($branch !== '' && ! in_array($branch, $branches, true)) {
                    $branches[] = $branch;
                }
            }
        }

        if (! in_array('Vĩnh Phúc', $branches, true)) {
            $branches[] = 'Vĩnh Phúc';
        }

        return $branches;
    }

    /**
     * @return array<int, array{no: int, phase: string, name: string}>
     */
    private function responsibilities(): array
    {
        return [
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
    }

    /**
     * @param  array<int, array<string, string>>  $staffDirectory
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<int, array{id: string, name: string, count: int, branch: string, unassigned?: bool}>
     */
    private function managers(array $staffDirectory, array $projects = []): array
    {
        $managers = [];
        $index = 0;

        foreach ($staffDirectory as $staff) {
            if (! str_contains($staff['role'], 'Quản lý')) {
                continue;
            }

            $count = 0;
            if ($projects !== []) {
                $count = count(array_filter($projects, static fn (array $p): bool => $p['manager_name'] === $staff['name']));
            } else {
                $counts = [13, 11, 6, 5];
                $count = $counts[$index] ?? 4;
            }

            $managers[] = [
                'id' => 'M'.($index + 1),
                'name' => $staff['name'],
                'count' => $count,
                'branch' => explode(' - ', $staff['branch'])[0],
            ];

            $index++;
        }

        $unassignedCount = 0;
        if ($projects !== []) {
            $unassignedCount = count(array_filter($projects, static fn (array $p): bool => (bool) ($p['unassigned'] ?? false) || $p['manager_name'] === 'Chưa phân công' || $p['manager_id'] === 'M0'));
        } else {
            $unassignedCount = 3;
        }

        $managers[] = [
            'id' => 'M0',
            'name' => 'Chưa phân công',
            'count' => $unassignedCount,
            'branch' => '—',
            'unassigned' => true,
        ];

        return $managers;
    }

    /**
     * @param  array<int, array<string, string>>  $staffDirectory
     * @return list<string>
     */
    private function specialists(array $staffDirectory): array
    {
        return array_values(array_map(
            static fn (array $staff): string => $staff['name'],
            array_filter($staffDirectory, static fn (array $staff): bool => str_contains($staff['role'], 'Chuyên viên'))
        ));
    }

    /**
     * @param  array<int, array<string, mixed>>  $managers
     * @param  list<string>  $specialists
     * @param  list<string>  $branches
     * @return array<int, array<string, mixed>>
     */
    private function projects(
        CarbonImmutable $today,
        array $status,
        array $branches,
        array $managers,
        array $specialists
    ): array {
        $keyCustomers = ['Goertek', 'Canon', 'Luxshare', 'Foxconn', 'Samsung SEV', 'Pegatron', 'BYD', 'HIP', 'Compal', 'Fukang', 'Amkor', 'JinkoSolar'];
        $normalCustomers = ['New Wing', 'Việt Hưng', 'Fuyu', 'Tongwei', 'Crystal', 'Sao Vàng', 'An Phát', 'Hồng Hải', 'Maple', 'Sunny', 'Bujeon', 'JA Solar', 'Lite-On', 'Deli', 'Tân Á', 'Hoa Sen', 'Trần Anh', 'KH Vina', 'Phú Thái'];
        $methods = ['Cộng tác viên', 'Tự tuyển', 'Đối tác cung ứng', 'Quảng cáo tuyển', 'Giới thiệu nội bộ'];
        $policies = ['Lương cứng + tăng ca', 'Khoán sản phẩm', 'Lương cứng + KPI', 'Hỗ trợ ở + ăn ca'];
        $products = ['Cung ứng LĐ thời vụ', 'Cung ứng LĐ chính thức', 'Khoán việc dây chuyền', 'Vệ sinh công nghiệp', 'Outsourcing kho vận'];
        $keyIndexes = [1, 3, 6, 8, 11, 14, 17, 20, 24, 28, 32, 36];
        $pausedIndexes = [2, 5, 7, 13, 16, 19, 23, 26, 29, 31, 34, 37, 38];
        $projects = [];
        $globalIndex = 0;

        foreach ($managers as $manager) {
            for ($i = 0; $i < $manager['count']; $i++) {
                $globalIndex++;
                $isKey = in_array($globalIndex, $keyIndexes, true);
                $customerType = $isKey ? 'Trọng điểm' : 'Thông thường';
                $customerPool = $isKey ? $keyCustomers : $normalCustomers;
                $customer = $customerPool[$globalIndex % count($customerPool)];
                $branch = $branches[$globalIndex % count($branches)];
                $projectStatus = in_array($globalIndex, $pausedIndexes, true) ? $status['paused'] : $status['operating'];
                $demand = 120 + (($globalIndex * 23) % 380);
                $ratio = $this->projectFillRatio($projectStatus, $status, $globalIndex);
                $actual = (int) round($demand * min($ratio, 1.05));
                $progress = $projectStatus === $status['paused'] ? 8 + ($globalIndex % 6) : 9 + ($globalIndex % 8);
                $teamSize = 1 + ($globalIndex % 2);
                $team = [];

                for ($t = 0; $t < $teamSize; $t++) {
                    $team[] = $specialists[($globalIndex + $t) % count($specialists)];
                }

                $contractStart = $today->subDays(60 + ($globalIndex * 6));
                $endOffset = match (true) {
                    $globalIndex % 8 === 0 => 12,
                    $globalIndex % 8 === 4 => 24,
                    default => 90 + ($globalIndex * 5),
                };
                $contractEnd = $today->addDays($endOffset);

                $projects[] = [
                    'id' => 'P'.str_pad((string) $globalIndex, 3, '0', STR_PAD_LEFT),
                    'code' => 'DA-'.str_pad((string) $globalIndex, 3, '0', STR_PAD_LEFT),
                    'name' => $customer.' - '.$branch.' (Đợt '.(1 + ($globalIndex % 3)).')',
                    'customer' => $customer,
                    'customer_type' => $customerType,
                    'branch' => $branch,
                    'product' => $products[$globalIndex % count($products)],
                    'method' => $methods[$globalIndex % count($methods)],
                    'policy' => $policies[$globalIndex % count($policies)],
                    'unit_price' => 28 + ($globalIndex % 18),
                    'recruit_status' => $projectStatus === $status['operating'] ? 'Đang tuyển' : 'Dừng tuyển',
                    'manager_id' => $manager['id'],
                    'manager_name' => $manager['name'],
                    'unassigned' => (bool) ($manager['unassigned'] ?? false),
                    'team' => $team,
                    'status' => $projectStatus,
                    'demand' => $demand,
                    'actual' => $actual,
                    'fill_rate' => $demand > 0 ? $actual / $demand : 0,
                    'shortage' => max(0, $demand - $actual),
                    'progress' => $progress,
                    'progress_pct' => (int) round(($progress / 21) * 100),
                    'contract_start' => $contractStart,
                    'contract_end' => $contractEnd,
                    'contract_days_left' => $endOffset,
                    'paused_days' => $projectStatus === $status['paused'] ? 4 + ($globalIndex % 16) : 0,
                    'reported_today' => $projectStatus === $status['operating'] && ($globalIndex % 5 !== 0),
                    'docs' => [
                        ['name' => 'Hợp đồng dịch vụ.pdf', 'type' => 'Hợp đồng'],
                        ['name' => 'Bảng lương T5-2026.xlsx', 'type' => 'Bảng lương'],
                        ['name' => 'Danh sách ứng viên.xlsx', 'type' => 'Tuyển dụng'],
                    ],
                ];
            }
        }

        return $projects;
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function todayLogs(array &$projects, CarbonImmutable $today): array
    {
        $ranks = ['A', 'A', 'B', 'B', 'C'];
        $logs = [];

        foreach ($projects as $index => &$project) {
            if (! $project['reported_today']) {
                continue;
            }

            $registered = 8 + (($index * 3) % 22);
            $interviewed = (int) round($registered * (0.6 + (($index % 4) / 20)));
            $passed = (int) round($interviewed * (0.6 + (($index % 3) / 20)));
            $started = (int) round($passed * (0.7 + (($index % 3) / 20)));
            $absent = (int) round($project['demand'] * (0.03 + ((($index * 7) % 9) / 100)));
            $project['today_absent'] = $absent;

            $logs[] = [
                'project_id' => $project['id'],
                'code' => $project['code'],
                'project' => $project['name'],
                'branch' => $project['branch'],
                'customer' => $project['customer'],
                'manager' => $project['manager_name'],
                'demand' => $project['demand'],
                'method' => $project['method'],
                'actual' => $project['actual'],
                'absent' => $absent,
                'registered' => $registered,
                'interviewed' => $interviewed,
                'passed' => $passed,
                'started' => $started,
                'partner_trial' => $index % 4,
                'conversion' => $registered > 0 ? $started / $registered : 0,
                'rank' => $ranks[$index % count($ranks)],
                'issues' => $index % 6 === 0 ? 'Thiếu LĐ ca đêm, đang tuyển bổ sung' : ($index % 9 === 0 ? 'Khách tăng đột biến đơn hàng' : ''),
                'reported_at' => str_pad((string) (7 + ($index % 9)), 2, '0', STR_PAD_LEFT).':'.str_pad((string) (($index * 13) % 60), 2, '0', STR_PAD_LEFT),
                'reporter' => $project['team'][0],
                'approved' => $index % 3 !== 0,
                'date' => $today,
                'date_key' => $today->toDateString(),
            ];
        }

        unset($project);

        return $logs;
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<int, array<string, mixed>>
     */
    private function reportHistory(array $projects, CarbonImmutable $today): array
    {
        $ranks = ['A', 'A', 'B', 'B', 'C'];
        $history = [];

        for ($dayOffset = 5; $dayOffset >= 0; $dayOffset--) {
            $date = $today->subDays($dayOffset);

            foreach ($projects as $index => $project) {
                if ($project['status'] !== 'Đang vận hành') {
                    continue;
                }

                $reported = $dayOffset === 0 ? $project['reported_today'] : (($index + $dayOffset) % 6 !== 0);

                if (! $reported) {
                    continue;
                }

                $seed = ($index * 3) + ($dayOffset * 7);
                $registered = 8 + ($seed % 22);
                $interviewed = (int) round($registered * (0.6 + (($seed % 4) / 20)));
                $passed = (int) round($interviewed * (0.6 + (($seed % 3) / 20)));
                $started = (int) round($passed * (0.7 + (($seed % 3) / 20)));

                $history[] = [
                    'date' => $date,
                    'date_key' => $date->toDateString(),
                    'project_id' => $project['id'],
                    'code' => $project['code'],
                    'customer' => $project['customer'],
                    'project' => $project['name'],
                    'branch' => $project['branch'],
                    'manager' => $project['manager_name'],
                    'demand' => $project['demand'],
                    'method' => $project['method'],
                    'registered' => $registered,
                    'interviewed' => $interviewed,
                    'passed' => $passed,
                    'started' => $started,
                    'partner_trial' => $seed % 4,
                    'conversion' => $registered > 0 ? $started / $registered : 0,
                    'rank' => $ranks[$seed % count($ranks)],
                    'reporter' => $project['team'][0],
                    'reported_at' => str_pad((string) (7 + ($seed % 9)), 2, '0', STR_PAD_LEFT).':'.str_pad((string) (($seed * 7) % 60), 2, '0', STR_PAD_LEFT),
                    'approved' => ($index + $dayOffset) % 3 !== 0,
                ];
            }
        }

        return $history;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function receivables(CarbonImmutable $today): array
    {
        $rows = [
            ['id' => 'CN01', 'customer' => 'HIP', 'amount' => 29, 'due' => 5, 'note' => 'Phí dịch vụ tháng 5'],
            ['id' => 'CN02', 'customer' => 'Luxshare', 'amount' => 106, 'due' => 5, 'note' => 'Phí cung ứng LĐ tháng 5'],
            ['id' => 'CN03', 'customer' => 'Goertek', 'amount' => 58, 'due' => 2, 'note' => 'Khoán việc dây chuyền'],
            ['id' => 'CN04', 'customer' => 'Foxconn', 'amount' => 75, 'due' => -3, 'note' => 'Quá hạn - cần đối soát'],
            ['id' => 'CN05', 'customer' => 'Canon', 'amount' => 42, 'due' => 12, 'note' => 'Phí dịch vụ tháng 5'],
            ['id' => 'CN06', 'customer' => 'Pegatron', 'amount' => 33, 'due' => 20, 'note' => 'Vệ sinh công nghiệp'],
            ['id' => 'CN07', 'customer' => 'BYD', 'amount' => 91, 'due' => 28, 'note' => 'Outsourcing kho vận'],
            ['id' => 'CN08', 'customer' => 'Samsung SEV', 'amount' => 120, 'due' => 41, 'note' => 'Cung ứng LĐ chính thức'],
            ['id' => 'CN09', 'customer' => 'Compal', 'amount' => 24, 'due' => 6, 'note' => 'Phí dịch vụ tháng 5'],
        ];

        return array_map(static fn (array $row): array => [
            'id' => $row['id'],
            'customer' => $row['customer'],
            'amount' => $row['amount'],
            'note' => $row['note'],
            'due_date' => $today->addDays($row['due']),
            'days_left' => $row['due'],
            'state' => $row['due'] < 0 ? 'Quá hạn' : ($row['due'] <= 7 ? 'Sắp đến hạn' : 'Trong hạn'),
            'paid' => false,
        ], $rows);
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $receivables
     * @return array<int, array<string, mixed>>
     */
    private function crmCustomers(array $projects, array $receivables, CarbonImmutable $today): array
    {
        $stages = ['Đang trao đổi', 'Ăn cafe', 'Ký hợp đồng dài hạn', 'Duy trì & mở rộng'];
        $relations = ['Cần chăm sóc', 'Bình thường', 'Tốt', 'Rất tốt'];
        $seeds = [
            ['name' => 'Goertek', 'stage' => 3, 'revenue' => 320, 'contact' => 'Mr. Wang', 'role' => 'GĐ Nhân sự', 'relation' => 2, 'last' => -4, 'next' => 6],
            ['name' => 'Luxshare', 'stage' => 3, 'revenue' => 280, 'contact' => 'Ms. Linh', 'role' => 'Trưởng phòng SX', 'relation' => 3, 'last' => -2, 'next' => 9],
            ['name' => 'Canon', 'stage' => 2, 'revenue' => 190, 'contact' => 'Mr. Sato', 'role' => 'HR Manager', 'relation' => 2, 'last' => -7, 'next' => 3],
            ['name' => 'Foxconn', 'stage' => 2, 'revenue' => 240, 'contact' => 'Ms. Mai', 'role' => 'Phụ trách tuyển dụng', 'relation' => 1, 'last' => -10, 'next' => 2],
            ['name' => 'Samsung SEV', 'stage' => 3, 'revenue' => 410, 'contact' => 'Mr. Kim', 'role' => 'Giám đốc vận hành', 'relation' => 3, 'last' => -1, 'next' => 14],
            ['name' => 'Pegatron', 'stage' => 1, 'revenue' => 0, 'contact' => 'Ms. Hoa', 'role' => 'Trưởng phòng HC', 'relation' => 1, 'last' => -15, 'next' => 1],
            ['name' => 'BYD', 'stage' => 1, 'revenue' => 0, 'contact' => 'Mr. Chen', 'role' => 'Plant Manager', 'relation' => 0, 'last' => -20, 'next' => 4],
            ['name' => 'HIP', 'stage' => 2, 'revenue' => 95, 'contact' => 'Anh Tuấn', 'role' => 'Quản đốc', 'relation' => 2, 'last' => -5, 'next' => 7],
            ['name' => 'Amkor', 'stage' => 0, 'revenue' => 0, 'contact' => 'Ms. Yến', 'role' => 'HR Lead', 'relation' => 0, 'last' => -25, 'next' => 5],
            ['name' => 'Compal', 'stage' => 2, 'revenue' => 130, 'contact' => 'Mr. Lin', 'role' => 'Trưởng ca', 'relation' => 2, 'last' => -6, 'next' => 10],
            ['name' => 'Fukang', 'stage' => 1, 'revenue' => 0, 'contact' => 'Chị Vân', 'role' => 'Phụ trách HC', 'relation' => 1, 'last' => -18, 'next' => 8],
            ['name' => 'JinkoSolar', 'stage' => 3, 'revenue' => 175, 'contact' => 'Mr. Zhao', 'role' => 'Operation Dir.', 'relation' => 3, 'last' => -3, 'next' => 12],
        ];
        $notes = [
            'KH hài lòng chất lượng LĐ, muốn tăng quy mô Q3.',
            'Đề nghị giảm 3% đơn giá - đang thương lượng.',
            'Cần bổ sung 30 LĐ ca đêm gấp trong tuần.',
            'Đối tác lâu năm, ưu tiên chăm sóc định kỳ.',
            'Mới tiếp cận, cần buổi gặp giới thiệu năng lực.',
            'Có nguy cơ chuyển sang nhà cung cấp khác - cần giữ.',
        ];

        return array_map(function (array $seed, int $index) use ($projects, $receivables, $today, $stages, $relations, $notes): array {
            $customerProjects = array_values(array_filter($projects, static fn (array $project): bool => $project['customer'] === $seed['name']));
            $customerReceivables = array_values(array_filter($receivables, static fn (array $receivable): bool => $receivable['customer'] === $seed['name']));

            return [
                'name' => $seed['name'],
                'type' => 'Trọng điểm',
                'stage' => $stages[$seed['stage']],
                'stage_idx' => $seed['stage'],
                'relationship' => $relations[$seed['relation']],
                'contact_name' => $seed['contact'],
                'contact_role' => $seed['role'],
                'revenue_monthly' => $seed['revenue'],
                'project_count' => count($customerProjects),
                'branches' => array_values(array_unique(array_column($customerProjects, 'branch'))),
                'last_meeting' => $today->addDays($seed['last']),
                'next_meeting' => $today->addDays($seed['next']),
                'notes' => [$notes[$index % count($notes)], $notes[($index + 3) % count($notes)]],
                'projects' => $customerProjects,
                'receivables' => $customerReceivables,
            ];
        }, $seeds, array_keys($seeds));
    }

    /**
     * @return array<int, array{date: CarbonImmutable, actual: int, target: int, absent: int, new_in: int}>
     */
    private function dailySeries(CarbonImmutable $today): array
    {
        $series = [];

        $dbData = OperationRecruitmentReport::query()
            ->selectRaw('report_date, SUM(started) as total_started, SUM(demand) as total_demand, SUM(registered) as total_registered')
            ->where('report_date', '>=', $today->subDays(29)->toDateString())
            ->where('report_date', '<=', $today->toDateString())
            ->groupBy('report_date')
            ->get()
            ->keyBy(fn ($r) => $r->report_date->toDateString());

        for ($day = 29; $day >= 0; $day--) {
            $date = $today->subDays($day);
            $dateKey = $date->toDateString();

            $record = $dbData->get($dateKey);

            if ($record && (int) $record->total_demand > 0) {
                $target = (int) $record->total_demand;
                $actual = (int) $record->total_started;
                $absent = (int) round($actual * 0.05);
                $newIn = (int) $record->total_registered;
            } else {
                $weekendDrop = $date->dayOfWeek === 0 ? 0.86 : 1;
                $trend = 1 + ((29 - $day) * 0.004);
                $target = 9800;
                $actual = (int) round(($target * 0.9 * $weekendDrop * $trend) - (($day % 3) * 40));
                $absent = (int) round($actual * (0.05 + (($day % 4) / 100)));
                $newIn = 20 + ($day % 13);
            }

            $series[] = [
                'date' => $date,
                'actual' => $actual,
                'target' => $target,
                'absent' => $absent,
                'new_in' => $newIn,
            ];
        }

        return $series;
    }

    /**
     * @return array<int, array{month: string, projects: int, operating: int}>
     */
    private function monthlyGrowth(): array
    {
        $months = [];
        $start = now()->subMonths(11);

        for ($i = 0; $i < 12; $i++) {
            $monthDate = $start->copy()->addMonths($i);
            $monthLabel = 'T'.$monthDate->month.'/'.$monthDate->format('y');

            $monthStart = $monthDate->startOfMonth()->toDateString();
            $monthEnd = $monthDate->endOfMonth()->toDateString();

            $totalProjects = OperationProject::query()
                ->where('contract_start', '<=', $monthEnd)
                ->where('contract_end', '>=', $monthStart)
                ->count();

            $operatingProjects = OperationProject::query()
                ->where('contract_start', '<=', $monthEnd)
                ->where('contract_end', '>=', $monthStart)
                ->where('status', 'Đang vận hành')
                ->count();

            if ($totalProjects === 0) {
                $seedMap = [
                    'T7/25' => [22, 16],
                    'T8/25' => [24, 17],
                    'T9/25' => [27, 19],
                    'T10/25' => [29, 20],
                    'T11/25' => [31, 21],
                    'T12/25' => [32, 22],
                    'T1/26' => [33, 22],
                    'T2/26' => [34, 23],
                    'T3/26' => [35, 23],
                    'T4/26' => [36, 24],
                    'T5/26' => [37, 24],
                    'T6/26' => [38, 25],
                ];
                $totalProjects = $seedMap[$monthLabel][0] ?? 20;
                $operatingProjects = $seedMap[$monthLabel][1] ?? 15;
            }

            $months[] = [
                'month' => $monthLabel,
                'projects' => $totalProjects,
                'operating' => $operatingProjects,
            ];
        }

        return $months;
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $todayLogs
     * @param  array<int, array<string, mixed>>  $managers
     * @param  list<string>  $specialists
     * @param  array<int, array<string, string>>  $staffDirectory
     * @return array<int, array<string, mixed>>
     */
    private function staff(array $projects, array $todayLogs, array $managers, array $specialists, array $staffDirectory): array
    {
        $staff = [];

        foreach (array_filter($managers, static fn (array $manager): bool => empty($manager['unassigned'])) as $manager) {
            $managerProjects = array_values(array_filter($projects, static fn (array $project): bool => $project['manager_id'] === $manager['id']));
            $staff[] = [
                'id' => $manager['id'],
                'name' => $manager['name'],
                'role' => 'Quản lý vận hành',
                'branch' => $manager['branch'],
                'employment_status' => $this->employmentStatus($manager['name'], $staffDirectory),
                'project_count' => count($managerProjects),
                'operating' => count(array_filter($managerProjects, static fn (array $project): bool => $project['status'] === 'Đang vận hành')),
                'avg_progress' => $this->average($managerProjects, 'progress_pct'),
                'overloaded' => count($managerProjects) > 10,
                'projects' => $managerProjects,
            ];
        }

        foreach ($specialists as $index => $name) {
            $specialistProjects = array_values(array_filter($projects, static fn (array $project): bool => in_array($name, $project['team'], true)));
            $projectIds = array_column($specialistProjects, 'id');
            $logs = array_values(array_filter($todayLogs, static fn (array $log): bool => in_array($log['project_id'], $projectIds, true)));
            $registered = array_sum(array_column($logs, 'registered'));
            $started = array_sum(array_column($logs, 'started'));

            $staff[] = [
                'id' => 'S'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'name' => $name,
                'role' => 'Chuyên viên vận hành',
                'branch' => $this->staffBranch($name, $staffDirectory) ?? '—',
                'employment_status' => $this->employmentStatus($name, $staffDirectory),
                'project_count' => count($specialistProjects),
                'operating' => count(array_filter($specialistProjects, static fn (array $project): bool => $project['status'] === 'Đang vận hành')),
                'avg_progress' => $this->average($specialistProjects, 'progress_pct'),
                'overloaded' => count($specialistProjects) > 5,
                'started' => $started,
                'conversion' => $registered > 0 ? $started / $registered : 0,
                'projects' => $specialistProjects,
            ];
        }

        return $staff;
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @param  array<int, array<string, mixed>>  $receivables
     * @param  array<int, array<string, mixed>>  $managers
     * @return array<int, array<string, mixed>>
     */
    private function alerts(array $projects, array $receivables, array $managers, array $status, CarbonImmutable $today): array
    {
        $alerts = [];
        $id = 0;
        $push = static function (string $level, string $rule, string $title, string $detail, array $ref = []) use (&$alerts, &$id): void {
            $id++;
            $alerts[] = [
                'id' => 'A'.$id,
                'level' => $level,
                'rule' => $rule,
                'title' => $title,
                'detail' => $detail,
                'ref' => $ref,
            ];
        };

        foreach ($projects as $project) {
            if ($project['status'] === $status['operating'] && ! $project['reported_today']) {
                $push('red', 'Chưa cập nhật số liệu', 'Chưa cập nhật số liệu sau 18:00', $project['code'].' - '.$project['name'].' - phụ trách: '.$project['manager_name'], ['code' => $project['code']]);
            }

            if ($project['status'] === $status['operating'] && $project['fill_rate'] < 0.85) {
                $push(
                    $project['fill_rate'] < 0.70 ? 'red' : 'amber',
                    'Thiếu nhân sự',
                    'Thiếu '.$project['shortage'].' LĐ (hiện '.$project['actual'].'/'.$project['demand'].')',
                    $project['code'].' - '.$project['name'].' - lấp đầy '.(int) round($project['fill_rate'] * 100).'%',
                    ['code' => $project['code']],
                );
            }

            if ($project['status'] === $status['paused'] && $project['paused_days'] > 7) {
                $push(
                    $project['paused_days'] > 14 ? 'red' : 'amber',
                    'Tạm dừng quá hạn',
                    'Tạm dừng '.$project['paused_days'].' ngày chưa cập nhật',
                    $project['code'].' - '.$project['name'].' - KH: '.$project['customer'],
                    ['code' => $project['code']],
                );
            }

            if ($project['contract_days_left'] >= 0 && $project['contract_days_left'] <= 30) {
                $push(
                    'amber',
                    'Hợp đồng sắp hết hạn',
                    'Hợp đồng còn '.$project['contract_days_left'].' ngày',
                    $project['code'].' - '.$project['name'].' - KH: '.$project['customer'],
                    ['code' => $project['code']],
                );
            }
        }

        foreach ($receivables as $receivable) {
            if ($receivable['days_left'] > 7) {
                continue;
            }

            $when = $receivable['days_left'] < 0
                ? 'quá hạn '.abs($receivable['days_left']).' ngày'
                : 'còn '.$receivable['days_left'].' ngày';
            $push(
                $receivable['days_left'] <= 2 ? 'red' : 'amber',
                'Công nợ đến hạn',
                $receivable['customer'].': '.$receivable['amount'].' triệu - '.$when,
                'Hạn '.$receivable['due_date']->format('d/m').' - '.$receivable['note'],
                ['customer' => $receivable['customer']],
            );
        }

        foreach (array_filter($managers, static fn (array $manager): bool => empty($manager['unassigned'])) as $manager) {
            if ($manager['count'] > 10) {
                $push('amber', 'Quản lý quá tải', $manager['name'].' đang gánh '.$manager['count'].' dự án', 'Vượt ngưỡng 10 dự án - nên cân bằng tải', ['manager' => $manager['name']]);
            }
        }

        usort($alerts, static fn (array $a, array $b): int => $a['level'] === $b['level'] ? 0 : ($a['level'] === 'red' ? -1 : 1));

        return $alerts;
    }

    /**
     * @param  array<int, array<string, mixed>>  $projects
     * @return array<string, int|float>
     */
    public function kpiFor(array $projects, array $status): array
    {
        $operating = array_values(array_filter($projects, static fn (array $project): bool => $project['status'] === $status['operating']));
        $sumDemand = array_sum(array_column($operating, 'demand'));
        $sumActual = array_sum(array_column($operating, 'actual'));

        return [
            'total' => count($projects),
            'operating' => count($operating),
            'paused' => count(array_filter($projects, static fn (array $project): bool => $project['status'] === $status['paused'])),
            'sum_demand' => $sumDemand,
            'sum_actual' => $sumActual,
            'fill_rate' => $sumDemand > 0 ? $sumActual / $sumDemand : 0,
            'shortage' => array_sum(array_column($operating, 'shortage')),
            'key' => count(array_filter($projects, static fn (array $project): bool => $project['customer_type'] === 'Trọng điểm')),
            'normal' => count(array_filter($projects, static fn (array $project): bool => $project['customer_type'] === 'Thông thường')),
        ];
    }

    private function projectFillRatio(string $projectStatus, array $status, int $index): float
    {
        if ($projectStatus === $status['paused']) {
            return 0.30 + (($index % 9) / 100);
        }

        return match (true) {
            $index % 6 === 0 => 0.62,
            $index % 5 === 0 => 0.78,
            default => 0.88 + (($index % 11) / 100),
        };
    }

    private function normalizeBranch(string $branch): string
    {
        return trim(str_replace('Chi nhánh ', '', $branch));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function average(array $items, string $key): int
    {
        if ($items === []) {
            return 0;
        }

        return (int) round(array_sum(array_column($items, $key)) / count($items));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<string, int>
     */
    private function countBy(array $items, string $key): array
    {
        $result = [];

        foreach ($items as $item) {
            $value = (string) $item[$key];
            $result[$value] = ($result[$value] ?? 0) + 1;
        }

        return $result;
    }

    /**
     * @param  array<int, array<string, string>>  $staffDirectory
     */
    private function staffBranch(string $name, array $staffDirectory): ?string
    {
        foreach ($staffDirectory as $staff) {
            if ($staff['name'] === $name) {
                return $staff['branch'];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, string>>  $staffDirectory
     */
    private function employmentStatus(string $name, array $staffDirectory): string
    {
        foreach ($staffDirectory as $staff) {
            if ($staff['name'] === $name) {
                return $staff['status'];
            }
        }

        return 'Chính thức';
    }
}
