<?php

declare(strict_types=1);

namespace App\Livewire\DailyReports;

use App\DTOs\DailyReportDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\User;
use App\Services\DailyReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Báo cáo Ngày')]
final class DailyReportIndex extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    // ── Filters ──────────────────────────────────────────────────────────────
    public string $filterDate = '';

    public int $filterUserId = 0;

    public string $search = '';

    public string $viewMode = 'mine'; // 'mine' | 'all'

    public string $viewType = 'table'; // 'table' | 'calendar'

    // ── Create form ───────────────────────────────────────────────────────────
    public string $formDate = '';

    public string $formWorkDone = '';

    public string $formPlanTomorrow = '';

    public string $formIssues = '';

    // ── Edit ──────────────────────────────────────────────────────────────────
    public int $editingId = 0;

    // ── Detail view ───────────────────────────────────────────────────────────
    public int $viewingId = 0;

    // ── Day reports view properties (for Director/non-creators) ────────────────
    public string $selectedDateStr = '';

    public array $dayReports = [];

    protected function rules(): array
    {
        return [
            'formDate' => ['required', 'date'],
            'formWorkDone' => ['required', 'string', 'min:10', 'max:3000'],
            'formPlanTomorrow' => ['nullable', 'string', 'max:2000'],
            'formIssues' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected array $messages = [
        'formDate.required' => 'Vui lòng chọn ngày báo cáo.',
        'formDate.date' => 'Ngày báo cáo không hợp lệ.',
        'formDate.unique' => 'Bạn đã có báo cáo cho ngày này rồi.',
        'formWorkDone.required' => 'Vui lòng nhập công việc đã thực hiện.',
        'formWorkDone.min' => 'Mô tả công việc cần ít nhất 10 ký tự.',
    ];

    public function mount(): void
    {
        Gate::authorize('viewAny', DailyReport::class);

        $this->formDate = now()->toDateString();
        $this->filterDate = now()->toDateString();

        // Viewer-only (e.g. Director): has report.view but NOT report.create → see all
        // Staff: has report.create → see own only by default
        $canViewAll = $this->resolveCanViewAll();
        $this->viewMode = $canViewAll ? 'all' : 'mine';
    }

    /**
     * A user can view ALL reports when they have report.view
     * but do NOT have report.create (e.g. Director).
     * SuperAdmin bypasses everything via before() in policies.
     */
    private function resolveCanViewAll(): bool
    {
        $user = Auth::user();
        if (! $user) {
            return false;
        }
        // SuperAdmin → always yes
        if ($user->hasRole(RoleEnum::SuperAdmin->value)) {
            return true;
        }

        // View-only role (Director): has report.view but NOT report.create
        return $user->can(PermissionEnum::ReportView->value)
            && ! $user->can(PermissionEnum::ReportCreate->value);
    }

    public function updatedFilterDate(): void
    {
        $this->resetPage();
        $this->dispatch('reports:filter-changed');
    }

    public function updatedFilterUserId(): void
    {
        $this->resetPage();
        $this->dispatch('reports:filter-changed');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
        $this->dispatch('reports:filter-changed');
    }

    public function updatedViewMode(): void
    {
        $this->resetPage();
        $this->filterUserId = 0;
        $this->filterDate = now()->toDateString();
        $this->search = '';
        $this->dispatch('reports:filter-changed');
    }

    // ── Open Create Modal ────────────────────────────────────────────────────
    public function openCreateModal(): void
    {
        Gate::authorize('create', DailyReport::class);

        $this->resetForm();
        $this->editingId = 0;
        $this->formDate = now()->toDateString();
        $this->resetValidation();
        $this->dispatch('report-create:show');
    }

    // ── Open Edit Modal ──────────────────────────────────────────────────────
    public function openEditModal(int $id): void
    {
        $report = DailyReport::findOrFail($id);
        Gate::authorize('update', $report);

        $this->editingId = $id;
        $this->formDate = $report->report_date->toDateString();
        $this->formWorkDone = $report->work_done;
        $this->formPlanTomorrow = $report->plan_tomorrow ?? '';
        $this->formIssues = $report->issues ?? '';
        $this->resetValidation();
        $this->dispatch('report-create:show');
    }

    // ── Open Detail Modal ────────────────────────────────────────────────────
    public function openDetailModal(int $id): void
    {
        $report = DailyReport::with('user')->findOrFail($id);
        Gate::authorize('view', $report);

        $this->viewingId = $id;
        $this->dispatch('report-detail:show');
    }

    public function save(DailyReportService $service): void
    {
        if ($service->existsForDate((int) Auth::id(), $this->formDate, $this->editingId ?: null)) {
            $this->addError('formDate', 'Bạn đã có báo cáo cho ngày này rồi.');

            return;
        }

        $this->validate();

        $dto = DailyReportDTO::fromArray([
            'user_id' => Auth::id(),
            'report_date' => $this->formDate,
            'work_done' => $this->formWorkDone,
            'plan_tomorrow' => $this->formPlanTomorrow,
            'issues' => $this->formIssues,
        ]);

        if ($this->editingId > 0) {
            $report = DailyReport::findOrFail($this->editingId);
            Gate::authorize('update', $report);
            $service->updateReport($report, $dto);
            $message = 'Đã cập nhật báo cáo ngày '.$this->formDate.' thành công.';
        } else {
            Gate::authorize('create', DailyReport::class);
            $service->createReport($dto);
            $message = 'Đã tạo báo cáo ngày '.$this->formDate.' thành công.';
        }

        $this->dispatch('report-create:hide');
        $this->resetForm();
        $this->dispatch('reports:filter-changed');

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => $message,
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────
    public function delete(DailyReportService $service, int $id): void
    {
        $report = DailyReport::findOrFail($id);
        Gate::authorize('delete', $report);

        $date = $report->report_date->format('d/m/Y');
        $service->deleteReport($report);

        $this->dispatch('reports:filter-changed');

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Báo cáo ngày '.$date.' đã bị xóa.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    private function resetForm(): void
    {
        $this->formDate = now()->toDateString();
        $this->formWorkDone = '';
        $this->formPlanTomorrow = '';
        $this->formIssues = '';
        $this->editingId = 0;
    }

    public function showDayReports(string $dateStr, DailyReportService $service): void
    {
        Gate::authorize('viewAny', DailyReport::class);

        $parsed = Carbon::parse($dateStr)->tz(config('app.timezone'));
        $this->selectedDateStr = $parsed->format('d/m/Y');

        $canViewAll = $this->resolveCanViewAll();

        $userId = match (true) {
            $this->viewMode === 'mine' => (int) Auth::id(),
            $canViewAll && $this->filterUserId > 0 => $this->filterUserId,
            default => null,
        };

        $localDate = $parsed->toDateString();
        $reports = $service->getReportsInRange($localDate.' 00:00:00', $localDate.' 23:59:59', $userId);

        $this->dayReports = $reports->map(function (DailyReport $report) {
            return [
                'id' => $report->id,
                'user_name' => $report->user->name ?? 'N/A',
                'work_done' => $report->work_done,
                'plan_tomorrow' => $report->plan_tomorrow ?? '',
                'issues' => $report->issues ?? '',
                'created_at_formatted' => $report->created_at->format('H:i d/m/Y'),
                'can_edit' => auth()->user()?->can('update', $report) ?? false,
                'can_delete' => auth()->user()?->can('delete', $report) ?? false,
            ];
        })->toArray();

        $this->dispatch('report:open-day-reports');
    }

    public function openEditFromList(int $id): void
    {
        $this->dispatch('report:close-day-reports');
        $this->openEditModal($id);
    }

    public function deleteFromList(DailyReportService $service, int $id): void
    {
        $report = DailyReport::findOrFail($id);
        $dateStr = $report->report_date->toDateString();

        $this->delete($service, $id);

        // Refresh
        $this->showDayReports($dateStr, $service);
    }

    public function openCreateModalForDate(string $dateStr): void
    {
        Gate::authorize('create', DailyReport::class);

        $this->resetForm();
        $this->editingId = 0;
        $this->formDate = Carbon::parse($dateStr)->tz(config('app.timezone'))->toDateString();
        $this->resetValidation();
        $this->dispatch('report-create:show');
    }

    /**
     * Fetch daily report events in a date range for FullCalendar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(string $start, string $end, DailyReportService $service): array
    {
        Gate::authorize('viewAny', DailyReport::class);

        $canViewAll = $this->resolveCanViewAll();

        $userId = match (true) {
            $this->viewMode === 'mine' => (int) Auth::id(),
            $canViewAll && $this->filterUserId > 0 => $this->filterUserId,
            default => null,
        };

        $localStart = Carbon::parse($start)->tz(config('app.timezone'))->startOfDay()->toDateTimeString();
        $localEnd = Carbon::parse($end)->tz(config('app.timezone'))->endOfDay()->toDateTimeString();

        $reports = $service->getReportsInRange($localStart, $localEnd, $userId);

        return $reports->map(function (DailyReport $report) use ($canViewAll) {
            $summary = Str::limit($report->work_done, 35);
            $title = ($canViewAll && $this->viewMode === 'all')
                ? ($report->user->name ?? 'N/A').': '.$summary
                : $summary;

            $color = $report->issues ? 'warning' : 'success';

            return [
                'id' => $report->id,
                'title' => $title,
                'start' => $report->report_date->toDateString(),
                'allDay' => true,
                'classNames' => $this->getEventClasses($color),
                'user_name' => $report->user->name ?? 'N/A',
                'work_done' => $report->work_done,
                'plan_tomorrow' => $report->plan_tomorrow ?? '',
                'issues' => $report->issues ?? '',
                'report_date_formatted' => $report->report_date->format('d/m/Y'),
                'can_edit' => auth()->user()?->can('update', $report) ?? false,
                'can_delete' => auth()->user()?->can('delete', $report) ?? false,
            ];
        })->toArray();
    }

    private function getEventClasses(string $color): array
    {
        return match ($color) {
            'success' => ['bg-success-subtle', 'text-success', 'border-success', 'p-1', 'fw-semibold'],
            'warning' => ['bg-warning-subtle', 'text-warning', 'border-warning', 'p-1', 'fw-semibold'],
            default => ['bg-primary-subtle', 'text-primary', 'border-primary', 'p-1', 'fw-semibold'],
        };
    }

    public function render(DailyReportService $service): View
    {
        $canViewAll = $this->resolveCanViewAll();

        $userId = match (true) {
            $this->viewMode === 'mine' => (int) Auth::id(),
            $canViewAll && $this->filterUserId > 0 => $this->filterUserId,
            default => null,
        };

        $reports = $service->getReports(
            userId: $userId,
            date: $this->filterDate ?: null,
            search: $this->search ?: null,
        );

        $viewingReport = $this->viewingId > 0
            ? DailyReport::with('user')->find($this->viewingId)
            : null;

        $users = $canViewAll
            ? User::permission(PermissionEnum::ReportCreate->value)
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('livewire.daily-reports.daily-report-index', [
            'reports' => $reports,
            'canViewAll' => $canViewAll,
            'viewingReport' => $viewingReport,
            'users' => $users,
        ]);
    }
}
