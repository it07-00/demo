<?php

declare(strict_types=1);

namespace App\Livewire\WorkProgress;

use App\Enums\PermissionEnum;
use App\Models\DailyProgressEntry;
use App\Models\OperationProject;
use App\Models\User;
use App\Models\WeeklyTarget;
use App\Models\WeeklyTargetAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Tiến độ Công việc')]
final class WorkProgressIndex extends Component
{
    #[Url(except: '')]
    public string $weekOffset = '0';

    #[Url(as: 'project', except: '')]
    public string $filterProject = '';

    // ── Create/Edit target form ──
    public bool $showTargetModal = false;

    public int $editingTargetId = 0;

    public int $formProjectId = 0;

    public int $formCustomerDemand = 0;

    public int $formManagerAccepted = 0;

    /** @var array<int, array{user_id: int, quantity: int}> */
    public array $formAssignments = [];

    // ── Daily entry form ──
    public bool $showEntryModal = false;

    public int $entryAssignmentId = 0;

    public string $entryDate = '';

    public int $entryAchieved = 0;

    public string $entryNote = '';

    public int $editingEntryId = 0;

    public function mount(): void
    {
        $this->entryDate = now()->toDateString();
    }

    // ── Week helpers ──────────────────────────────────────────────────────────

    private function currentWeekStart(): CarbonImmutable
    {
        return CarbonImmutable::instance(
            now()->startOfWeek()->addWeeks((int) $this->weekOffset)
        );
    }

    public function previousWeek(): void
    {
        $this->weekOffset = (string) ((int) $this->weekOffset - 1);
    }

    public function nextWeek(): void
    {
        $this->weekOffset = (string) ((int) $this->weekOffset + 1);
    }

    public function currentWeek(): void
    {
        $this->weekOffset = '0';
    }

    // ── Target CRUD ──────────────────────────────────────────────────────────

    public function openCreateTarget(): void
    {
        if (! Auth::user()?->can(PermissionEnum::WorkProgressManage->value)) {
            abort(403);
        }

        $this->resetTargetForm();
        $this->showTargetModal = true;
    }

    public function openEditTarget(int $id): void
    {
        $target = WeeklyTarget::with('assignments')->findOrFail($id);

        $this->editingTargetId = $id;
        $this->formProjectId = $target->operation_project_id;
        $this->formCustomerDemand = $target->customer_demand;
        $this->formManagerAccepted = $target->manager_accepted;

        $this->formAssignments = $target->assignments->map(fn (WeeklyTargetAssignment $a): array => [
            'user_id' => $a->user_id,
            'quantity' => $a->assigned_quantity,
        ])->values()->toArray();

        $this->showTargetModal = true;
    }

    public function addAssignment(): void
    {
        $this->formAssignments[] = ['user_id' => 0, 'quantity' => 0];
    }

    public function removeAssignment(int $index): void
    {
        unset($this->formAssignments[$index]);
        $this->formAssignments = array_values($this->formAssignments);
    }

    public function splitEvenly(): void
    {
        $count = count($this->formAssignments);
        if ($count <= 0 || $this->formManagerAccepted <= 0) {
            return;
        }

        $each = (int) floor($this->formManagerAccepted / $count);
        $remainder = $this->formManagerAccepted - ($each * $count);

        foreach ($this->formAssignments as $i => &$assignment) {
            $assignment['quantity'] = $each + ($i < $remainder ? 1 : 0);
        }
        unset($assignment);
    }

    public function saveTarget(): void
    {
        $this->validate([
            'formProjectId' => ['required', 'exists:operation_projects,id'],
            'formCustomerDemand' => ['required', 'integer', 'min:1'],
            'formManagerAccepted' => ['required', 'integer', 'min:1'],
            'formAssignments' => ['required', 'array', 'min:1'],
            'formAssignments.*.user_id' => ['required', 'exists:users,id'],
            'formAssignments.*.quantity' => ['required', 'integer', 'min:1'],
        ], [
            'formProjectId.required' => 'Vui lòng chọn dự án.',
            'formCustomerDemand.required' => 'Vui lòng nhập nhu cầu khách hàng.',
            'formManagerAccepted.required' => 'Vui lòng nhập số QLVH nhận.',
            'formAssignments.required' => 'Cần ít nhất 1 chuyên viên.',
            'formAssignments.*.user_id.required' => 'Vui lòng chọn chuyên viên.',
            'formAssignments.*.quantity.required' => 'Vui lòng nhập số lượng.',
            'formAssignments.*.quantity.min' => 'Số lượng phải lớn hơn 0.',
        ]);

        $weekStart = $this->currentWeekStart();
        $weekEnd = $weekStart->addDays(6);

        $target = WeeklyTarget::query()->updateOrCreate(
            [
                'operation_project_id' => $this->formProjectId,
                'year' => $weekStart->year,
                'week_number' => $weekStart->weekOfYear,
            ],
            [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'customer_demand' => $this->formCustomerDemand,
                'manager_accepted' => $this->formManagerAccepted,
                'created_by' => Auth::id(),
            ],
        );

        // Sync assignments
        $existingIds = $target->assignments()->pluck('id', 'user_id')->toArray();
        $seenUserIds = [];

        foreach ($this->formAssignments as $assignment) {
            $userId = (int) $assignment['user_id'];
            $seenUserIds[] = $userId;

            WeeklyTargetAssignment::query()->updateOrCreate(
                [
                    'weekly_target_id' => $target->id,
                    'user_id' => $userId,
                ],
                [
                    'assigned_quantity' => (int) $assignment['quantity'],
                    'assigned_by' => Auth::id(),
                ],
            );
        }

        // Remove unassigned
        $target->assignments()
            ->whereNotIn('user_id', $seenUserIds)
            ->delete();

        $this->showTargetModal = false;
        $this->resetTargetForm();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => 'Đã lưu chỉ tiêu tuần.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    public function deleteTarget(int $id): void
    {
        WeeklyTarget::findOrFail($id)->delete();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Chỉ tiêu tuần đã bị xóa.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    // ── Daily Entry CRUD ─────────────────────────────────────────────────────

    public function openEntryModal(int $assignmentId, ?string $date = null): void
    {
        $this->entryAssignmentId = $assignmentId;
        $this->entryDate = $date ?? now()->toDateString();
        $this->entryAchieved = 0;
        $this->entryNote = '';
        $this->editingEntryId = 0;
        $this->showEntryModal = true;
    }

    public function openEditEntry(int $entryId): void
    {
        $entry = DailyProgressEntry::findOrFail($entryId);
        $this->editingEntryId = $entryId;
        $this->entryAssignmentId = $entry->weekly_target_assignment_id;
        $this->entryDate = $entry->entry_date->toDateString();
        $this->entryAchieved = $entry->achieved;
        $this->entryNote = $entry->note ?? '';
        $this->showEntryModal = true;
    }

    public function saveEntry(): void
    {
        $this->validate([
            'entryAssignmentId' => ['required', 'exists:weekly_target_assignments,id'],
            'entryDate' => ['required', 'date'],
            'entryAchieved' => ['required', 'integer', 'min:0'],
        ], [
            'entryDate.required' => 'Vui lòng chọn ngày.',
            'entryAchieved.required' => 'Vui lòng nhập số đạt được.',
            'entryAchieved.min' => 'Số đạt được phải >= 0.',
        ]);

        DailyProgressEntry::query()->updateOrCreate(
            [
                'weekly_target_assignment_id' => $this->entryAssignmentId,
                'entry_date' => $this->entryDate,
            ],
            [
                'achieved' => $this->entryAchieved,
                'note' => $this->entryNote ?: null,
                'created_by' => Auth::id(),
            ],
        );

        $this->showEntryModal = false;

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => 'Đã lưu tiến độ ngày '.$this->entryDate,
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    public function deleteEntry(int $id): void
    {
        DailyProgressEntry::findOrFail($id)->delete();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Đã xóa bản ghi tiến độ.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function resetTargetForm(): void
    {
        $this->editingTargetId = 0;
        $this->formProjectId = 0;
        $this->formCustomerDemand = 0;
        $this->formManagerAccepted = 0;
        $this->formAssignments = [];
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render(): View
    {
        $weekStart = $this->currentWeekStart();
        $weekEnd = $weekStart->addDays(6);
        $user = Auth::user();
        $canManage = $user?->can(PermissionEnum::WorkProgressManage->value) ?? false;

        $query = WeeklyTarget::query()
            ->with(['project', 'creator', 'assignments.user', 'assignments.dailyEntries'])
            ->where('week_start', $weekStart->toDateString());

        if ($this->filterProject !== '') {
            $query->where('operation_project_id', (int) $this->filterProject);
        }

        $targets = $query->orderBy('id')->get();

        // Build days of the week for header
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $weekStart->addDays($i);
            $weekDays[] = [
                'date' => $day->toDateString(),
                'label' => $day->isoFormat('ddd D/M'),
                'isToday' => $day->toDateString() === now()->toDateString(),
            ];
        }

        // Available projects and specialists for the form
        $projects = OperationProject::query()
            ->where('status', 'Đang vận hành')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'customer']);

        $specialists = User::query()
            ->whereNotNull('operation_role')
            ->where('operation_role', 'Chuyên viên vận hành')
            ->orderBy('name')
            ->get(['id', 'name', 'operation_branch']);

        // If no specialists seeded, fall back to all non-director users
        if ($specialists->isEmpty()) {
            $specialists = User::query()
                ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Giám đốc'))
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        return view('livewire.work-progress.work-progress-index', [
            'targets' => $targets,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekDays' => $weekDays,
            'canManage' => $canManage,
            'projects' => $projects,
            'specialists' => $specialists,
        ]);
    }
}
