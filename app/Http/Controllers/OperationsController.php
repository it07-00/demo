<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\OperationDataService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class OperationsController extends Controller
{
    public function projects(Request $request, OperationDataService $operations): View
    {
        $data = $operations->all();
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'branch' => (string) $request->query('branch', ''),
            'status' => (string) $request->query('status', ''),
            'type' => (string) $request->query('type', ''),
            'view' => $request->query('view') === 'table' ? 'table' : 'cards',
        ];

        return view('operations.projects', $data + [
            'filteredProjects' => $this->filterProjects($data['projects'], $filters),
            'filters' => $filters,
        ]);
    }

    public function staff(Request $request, OperationDataService $operations): View
    {
        $data = $operations->all();
        $role = (string) $request->query('role', 'all');
        $staff = array_values(array_filter(
            $data['staff'],
            static fn (array $person): bool => $role === 'all' || $person['role'] === $role
        ));

        usort($staff, static fn (array $a, array $b): int => $b['project_count'] <=> $a['project_count']);

        $selectedName = (string) $request->query('person', ($staff[0]['name'] ?? ''));
        $selected = $this->findByName($data['staff'], $selectedName) ?? ($staff[0] ?? null);
        $managers = array_values(array_filter($data['managers'], static fn (array $manager): bool => empty($manager['unassigned'])));
        usort($managers, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
        $suggestion = null;

        if ($managers !== [] && $managers[0]['count'] > 10) {
            $leastLoaded = $managers[count($managers) - 1];
            $suggestion = [
                'from' => $managers[0],
                'to' => $leastLoaded,
                'move' => (int) ceil(($managers[0]['count'] - $leastLoaded['count']) / 2),
            ];
        }

        return view('operations.staff', $data + [
            'roleFilter' => $role,
            'filteredStaff' => $staff,
            'selectedStaff' => $selected,
            'suggestion' => $suggestion,
        ]);
    }

    public function daily(Request $request, OperationDataService $operations): View
    {
        $data = $operations->all();
        $filters = [
            'date' => (string) $request->query('date', $data['today']->toDateString()),
            'project' => (string) $request->query('project', ''),
            'branch' => (string) $request->query('branch', ''),
        ];
        $history = $this->filterReportHistory($data['report_history'], $filters);
        $needProjects = array_values(array_filter(
            $data['projects'],
            static fn (array $project): bool => $project['status'] === 'Đang vận hành'
        ));
        $reportedTodayIds = array_unique(array_column(array_filter(
            $data['report_history'],
            static fn (array $record): bool => $record['date_key'] === $data['today']->toDateString()
        ), 'project_id'));
        $missing = array_values(array_filter(
            $needProjects,
            static fn (array $project): bool => ! in_array($project['id'], $reportedTodayIds, true)
        ));
        $todayRecords = array_values(array_filter(
            $data['report_history'],
            static fn (array $record): bool => $record['date_key'] === $data['today']->toDateString()
        ));
        $registered = array_sum(array_column($todayRecords, 'registered'));
        $started = array_sum(array_column($todayRecords, 'started'));
        $dates = array_values(array_unique(array_column($data['report_history'], 'date_key')));
        rsort($dates);

        return view('operations.daily', $data + [
            'filters' => $filters,
            'filteredHistory' => $history,
            'needProjects' => $needProjects,
            'missingProjects' => $missing,
            'reportDates' => $dates,
            'dailySummary' => [
                'reported' => count($reportedTodayIds),
                'need' => count($needProjects),
                'registered' => $registered,
                'started' => $started,
                'conversion' => $registered > 0 ? $started / $registered : 0,
            ],
        ]);
    }

    public function analytics(OperationDataService $operations): View
    {
        $data = $operations->all();
        $growth = $data['monthly_growth'];
        $first = $growth[0]['projects'];
        $last = $growth[count($growth) - 1]['projects'];
        $managerStats = $this->managerStats($data);
        $branchStats = $this->branchStats($data);
        $specialistRank = $this->specialistRank($data);
        $lowFill = array_values(array_filter(
            $data['projects'],
            static fn (array $project): bool => $project['status'] === 'Đang vận hành'
        ));

        usort($lowFill, static fn (array $a, array $b): int => $a['fill_rate'] <=> $b['fill_rate']);
        $lowFill = array_slice($lowFill, 0, 10);

        return view('operations.analytics', $data + [
            'growthPct' => (int) round((($last - $first) / $first) * 100),
            'managerStats' => $managerStats,
            'branchStats' => $branchStats,
            'specialistRank' => $specialistRank,
            'lowFillProjects' => $lowFill,
            'bestManager' => $this->firstSorted($managerStats, 'fill'),
            'bestBranch' => $this->firstSorted($branchStats, 'fill'),
        ]);
    }

    public function crm(OperationDataService $operations): View
    {
        $data = $operations->all();
        $byStage = [];

        foreach ($data['crm_stages'] as $stage) {
            $byStage[$stage] = array_values(array_filter(
                $data['crm_customers'],
                static fn (array $customer): bool => $customer['stage'] === $stage
            ));
        }

        return view('operations.crm', $data + [
            'customersByStage' => $byStage,
        ]);
    }

    public function alerts(Request $request, OperationDataService $operations): View
    {
        $data = $operations->all();
        $level = (string) $request->query('level', 'all');
        $rule = (string) $request->query('rule', '');
        $rules = array_values(array_unique(array_column($data['alerts'], 'rule')));
        $alerts = array_values(array_filter(
            $data['alerts'],
            static fn (array $alert): bool => ($level === 'all' || $alert['level'] === $level)
                && ($rule === '' || $alert['rule'] === $rule)
        ));

        return view('operations.alerts', $data + [
            'filteredAlerts' => $alerts,
            'levelFilter' => $level,
            'ruleFilter' => $rule,
            'alertRules' => $rules,
        ]);
    }

    /**
     * @param array<int, array<string, mixed>> $projects
     * @param array{q: string, branch: string, status: string, type: string, view: string} $filters
     * @return array<int, array<string, mixed>>
     */
    private function filterProjects(array $projects, array $filters): array
    {
        $query = mb_strtolower($filters['q']);

        return array_values(array_filter($projects, static function (array $project) use ($filters, $query): bool {
            if ($filters['branch'] !== '' && $project['branch'] !== $filters['branch']) {
                return false;
            }

            if ($filters['status'] !== '' && $project['status'] !== $filters['status']) {
                return false;
            }

            if ($filters['type'] !== '' && $project['customer_type'] !== $filters['type']) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = mb_strtolower($project['name'].' '.$project['customer'].' '.$project['code'].' '.$project['manager_name']);

            return str_contains($haystack, $query);
        }));
    }

    /**
     * @param array<int, array<string, mixed>> $history
     * @param array{date: string, project: string, branch: string} $filters
     * @return array<int, array<string, mixed>>
     */
    private function filterReportHistory(array $history, array $filters): array
    {
        $filtered = array_values(array_filter($history, static fn (array $record): bool => ($filters['date'] === '' || $record['date_key'] === $filters['date'])
            && ($filters['project'] === '' || $record['project_id'] === $filters['project'])
            && ($filters['branch'] === '' || $record['branch'] === $filters['branch'])));

        usort($filtered, static fn (array $a, array $b): int => ($b['date_key'] <=> $a['date_key']) ?: ($a['code'] <=> $b['code']));

        return $filtered;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array{name: string, fill: float, operating: int, total: int}>
     */
    private function managerStats(array $data): array
    {
        $stats = [];

        foreach (array_filter($data['managers'], static fn (array $manager): bool => empty($manager['unassigned'])) as $manager) {
            $operating = array_values(array_filter(
                $data['projects'],
                static fn (array $project): bool => $project['manager_id'] === $manager['id'] && $project['status'] === 'Đang vận hành'
            ));
            $fill = $operating === []
                ? 0
                : array_sum(array_column($operating, 'fill_rate')) / count($operating);

            $stats[] = [
                'name' => $manager['name'],
                'fill' => $fill,
                'operating' => count($operating),
                'total' => $manager['count'],
            ];
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array{branch: string, count: int, fill: float}>
     */
    private function branchStats(array $data): array
    {
        $stats = [];

        foreach ($data['branches'] as $branch) {
            $projects = array_values(array_filter($data['projects'], static fn (array $project): bool => $project['branch'] === $branch));
            $operating = array_values(array_filter($projects, static fn (array $project): bool => $project['status'] === 'Đang vận hành'));
            $demand = array_sum(array_column($operating, 'demand'));
            $actual = array_sum(array_column($operating, 'actual'));

            $stats[] = [
                'branch' => $branch,
                'count' => count($projects),
                'fill' => $demand > 0 ? $actual / $demand : 0,
            ];
        }

        return $stats;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<int, array{name: string, branch: string, started: int, conversion: float, projects: int, grade: string}>
     */
    private function specialistRank(array $data): array
    {
        $rank = [];

        foreach ($data['specialists'] as $specialist) {
            $records = array_values(array_filter($data['report_history'], function (array $record) use ($data, $specialist): bool {
                $project = $this->findById($data['projects'], $record['project_id']);

                return $project !== null && in_array($specialist, $project['team'], true);
            }));
            $started = array_sum(array_column($records, 'started'));
            $registered = array_sum(array_column($records, 'registered'));
            $rankAverage = $records === [] ? 0 : array_sum(array_map(static fn (array $record): int => match ($record['rank']) {
                'A' => 3,
                'B' => 2,
                default => 1,
            }, $records)) / count($records);
            $staff = $this->findByName($data['staff'], $specialist);

            $rank[] = [
                'name' => $specialist,
                'branch' => $staff['branch'] ?? '—',
                'started' => $started,
                'conversion' => $registered > 0 ? $started / $registered : 0,
                'projects' => count(array_unique(array_column($records, 'project_id'))),
                'grade' => $rankAverage >= 2.5 ? 'A' : ($rankAverage >= 1.5 ? 'B' : 'C'),
            ];
        }

        usort($rank, static fn (array $a, array $b): int => ($b['started'] <=> $a['started']) ?: ($b['conversion'] <=> $a['conversion']));

        return $rank;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function findById(array $items, string $id): ?array
    {
        foreach ($items as $item) {
            if ($item['id'] === $id) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function findByName(array $items, string $name): ?array
    {
        foreach ($items as $item) {
            if ($item['name'] === $name) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function firstSorted(array $items, string $key): array
    {
        usort($items, static fn (array $a, array $b): int => $b[$key] <=> $a[$key]);

        return $items[0] ?? [];
    }
}
