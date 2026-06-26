<?php

declare(strict_types=1);

namespace App\Livewire\DutySchedules;

use App\DTOs\DutyScheduleDTO;
use App\Enums\RoleEnum;
use App\Models\DutySchedule;
use App\Models\User;
use App\Services\DutyScheduleService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Lịch công tác')]
final class DutyScheduleIndex extends Component
{
    private DutyScheduleService $scheduleService;

    // Day schedules view properties (for Director/non-creators)
    public string $selectedDateStr = '';

    public array $daySchedules = [];

    // Filters
    public int $filterUserId = 0;

    // Form properties
    public ?int $scheduleId = null;

    public array $user_ids = [];

    public string $title = '';

    public ?string $description = null;

    public ?string $location = null;

    public string $start_at = '';

    public ?string $end_at = null;

    public string $label_color = 'primary';

    public bool $is_private = false;

    public ?string $successMessage = null;

    public function boot(DutyScheduleService $scheduleService): void
    {
        $this->scheduleService = $scheduleService;
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', DutySchedule::class);
    }

    public function updatedFilterUserId(): void
    {
        $this->dispatch('schedule:filter-changed');
    }

    protected function rules(): array
    {
        $startRules = ['required', 'date'];
        if ($this->scheduleId === null) {
            $startRules[] = 'after_or_equal:today';
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location' => ['nullable', 'string', 'max:255'],
            'start_at' => $startRules,
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'label_color' => ['required', 'string', 'in:primary,success,warning,danger,info,purple'],
            'is_private' => ['nullable', 'boolean'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ];
    }

    protected array $validationAttributes = [
        'title' => 'tiêu đề',
        'start_at' => 'thời gian bắt đầu',
        'end_at' => 'thời gian kết thúc',
        'label_color' => 'nhãn màu',
        'user_ids' => 'thành viên tham gia',
    ];

    /**
     * Fetch events in a date range for FullCalendar.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getEvents(string $start, string $end): array
    {
        Gate::authorize('viewAny', DutySchedule::class);

        $events = $this->scheduleService->getEventsInRange($start, $end, $this->filterUserId ?: null);

        return $events->map(function (DutySchedule $event) {
            $isCreator = auth()->id() === $event->created_by;
            $isSuperAdmin = auth()->user()?->hasRole(RoleEnum::SuperAdmin->value) ?? false;
            $isDirector = auth()->user()?->hasRole(RoleEnum::Director->value) ?? false;
            $isParticipant = $event->users->contains(auth()->id());
            $canSeeDetails = $isCreator || $isSuperAdmin || $isDirector || $isParticipant;

            $isPrivate = (bool) $event->is_private;
            $titlePrefix = $isPrivate ? '🔒 ' : '';
            $rawTitle = $isPrivate && ! $canSeeDetails ? 'Lịch riêng tư' : $event->title;

            // Format verbose title for FullCalendar grid
            if ($isPrivate && ! $canSeeDetails) {
                $title = $titlePrefix.$rawTitle;
            } else {
                $creatorName = $event->creator?->name ?? 'N/A';
                $participantsStr = '';
                if ($event->users->isNotEmpty()) {
                    $participantsNames = $event->users->pluck('name')->toArray();
                    $participantsStr = ' (với '.implode(', ', $participantsNames).')';
                }
                $title = $titlePrefix."{$creatorName}: {$rawTitle}{$participantsStr}";
            }

            $description = $isPrivate && ! $canSeeDetails ? null : $event->description;
            $location = $isPrivate && ! $canSeeDetails ? null : $event->location;

            return [
                'id' => $event->id,
                'title' => $title,
                'raw_title' => $titlePrefix.$rawTitle,
                'start' => $event->start_at->toIso8601String(),
                'end' => $event->end_at?->toIso8601String(),
                'description' => $description,
                'location' => $location,
                'classNames' => $this->getEventClasses($isPrivate && ! $canSeeDetails ? 'private' : $event->label_color),
                'label_color' => $event->label_color,
                'creator_name' => $event->creator?->name ?? 'N/A',
                'participants' => $event->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->toArray(),
                'can_edit' => auth()->user()?->can('update', $event) ?? false,
                'can_delete' => auth()->user()?->can('delete', $event) ?? false,
            ];
        })->toArray();
    }

    private function getEventClasses(string $labelColor): array
    {
        return match ($labelColor) {
            'success' => ['bg-success-subtle', 'text-success', 'border-success', 'p-1', 'fw-semibold'],
            'warning' => ['bg-warning-subtle', 'text-warning', 'border-warning', 'p-1', 'fw-semibold'],
            'danger' => ['bg-danger-subtle', 'text-danger', 'border-danger', 'p-1', 'fw-semibold'],
            'info' => ['bg-info-subtle', 'text-info', 'border-info', 'p-1', 'fw-semibold'],
            'purple' => ['bg-purple-subtle', 'text-purple', 'border-purple', 'p-1', 'fw-semibold'],
            'private' => ['bg-secondary-subtle', 'text-secondary', 'border-secondary', 'p-1', 'fw-semibold', 'opacity-75'],
            default => ['bg-primary-subtle', 'text-primary', 'border-primary', 'p-1', 'fw-semibold'],
        };
    }

    public function openCreate(string $dateStr): void
    {
        Gate::authorize('create', DutySchedule::class);

        // Prevent creating duty schedules in the past
        $selectedDate = date('Y-m-d', strtotime($dateStr));
        $todayDate = date('Y-m-d');
        if ($selectedDate < $todayDate) {
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Thao tác không hợp lệ',
                'text' => 'Không thể tạo lịch công tác cho ngày trong quá khứ!',
            ]);

            return;
        }

        $this->resetErrorBag();
        $this->scheduleId = null;
        $this->title = '';
        $this->description = null;
        $this->location = null;

        // Parse date and set default time to current hour
        $date = date('Y-m-d', strtotime($dateStr));
        $time = date('H:i');
        $this->start_at = "{$date}T{$time}";
        $this->end_at = null;
        $this->label_color = 'primary';
        $this->is_private = false;
        $this->user_ids = [];

        $this->dispatch('schedule:open-create');
    }

    public function save(): void
    {
        $this->validate();

        $dto = DutyScheduleDTO::fromArray([
            'title' => $this->title,
            'description' => $this->description,
            'location' => $this->location,
            'start_at' => $this->start_at,
            'end_at' => $this->end_at,
            'label_color' => $this->label_color,
            'is_private' => $this->is_private,
            'user_ids' => $this->user_ids,
        ]);

        if ($this->scheduleId !== null) {
            $schedule = DutySchedule::findOrFail($this->scheduleId);
            Gate::authorize('update', $schedule);

            $this->scheduleService->update($schedule, $dto);
            $message = 'Cập nhật lịch công tác thành công!';
        } else {
            Gate::authorize('create', DutySchedule::class);

            $this->scheduleService->create($dto);
            $message = 'Tạo lịch công tác thành công!';
        }

        $this->dispatch('schedule:saved', message: $message);
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $schedule = DutySchedule::with('users')->findOrFail($id);
        Gate::authorize('update', $schedule);

        $this->resetErrorBag();
        $this->scheduleId = $schedule->id;
        $this->title = $schedule->title;
        $this->description = $schedule->description;
        $this->location = $schedule->location;
        $this->start_at = $schedule->start_at->format('Y-m-d\TH:i');
        $this->end_at = $schedule->end_at?->format('Y-m-d\TH:i');
        $this->label_color = $schedule->label_color;
        $this->is_private = (bool) $schedule->is_private;
        $this->user_ids = $schedule->users->pluck('id')->toArray();

        $this->dispatch('schedule:open-edit');
    }

    public function delete(int $id): void
    {
        $schedule = DutySchedule::findOrFail($id);
        Gate::authorize('delete', $schedule);

        $this->scheduleService->delete($schedule);

        $this->dispatch('schedule:deleted', message: 'Xóa lịch công tác thành công!');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->scheduleId = null;
        $this->title = '';
        $this->description = null;
        $this->location = null;
        $this->start_at = '';
        $this->end_at = null;
        $this->label_color = 'primary';
        $this->is_private = false;
        $this->user_ids = [];
    }

    public function showDaySchedules(string $dateStr): void
    {
        Gate::authorize('viewAny', DutySchedule::class);

        $this->selectedDateStr = Carbon::parse($dateStr)->format('d/m/Y');

        $start = $dateStr.' 00:00:00';
        $end = $dateStr.' 23:59:59';

        $schedules = $this->scheduleService->getEventsInRange($start, $end, $this->filterUserId ?: null);

        $this->daySchedules = $schedules->map(function (DutySchedule $event) {
            $isCreator = auth()->id() === $event->created_by;
            $isSuperAdmin = auth()->user()?->hasRole(RoleEnum::SuperAdmin->value) ?? false;
            $isDirector = auth()->user()?->hasRole(RoleEnum::Director->value) ?? false;
            $isParticipant = $event->users->contains(auth()->id());
            $canSeeDetails = $isCreator || $isSuperAdmin || $isDirector || $isParticipant;

            $isPrivate = (bool) $event->is_private;
            $titlePrefix = $isPrivate ? '🔒 ' : '';
            $title = $isPrivate && ! $canSeeDetails ? 'Lịch riêng tư' : $event->title;
            $title = $titlePrefix.$title;

            $description = $isPrivate && ! $canSeeDetails ? null : $event->description;
            $location = $isPrivate && ! $canSeeDetails ? null : $event->location;

            return [
                'id' => $event->id,
                'title' => $title,
                'start_formatted' => $event->start_at->format('H:i d/m/Y'),
                'end_formatted' => $event->end_at?->format('H:i d/m/Y'),
                'description' => $description,
                'location' => $location,
                'label_color' => $isPrivate && ! $canSeeDetails ? 'private' : $event->label_color,
                'creator_name' => $event->creator?->name ?? 'N/A',
                'participants' => $event->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->toArray(),
                'can_edit' => auth()->user()?->can('update', $event) ?? false,
                'can_delete' => auth()->user()?->can('delete', $event) ?? false,
            ];
        })->toArray();

        $this->dispatch('schedule:open-day-schedules');
    }

    public function openEditFromList(int $id): void
    {
        $this->dispatch('schedule:close-day-schedules');
        $this->edit($id);
    }

    public function openCreateFromList(): void
    {
        $this->dispatch('schedule:close-day-schedules');

        $dateStr = Carbon::createFromFormat('d/m/Y', $this->selectedDateStr)->format('Y-m-d');
        $this->openCreate($dateStr);
    }

    public function deleteFromList(int $id): void
    {
        $this->delete($id);

        // Refresh
        $dateStr = Carbon::createFromFormat('d/m/Y', $this->selectedDateStr)->format('Y-m-d');
        $this->showDaySchedules($dateStr);
    }

    public function render(): View
    {
        $users = User::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.duty-schedules.duty-schedule-index', [
            'users' => $users,
        ]);
    }
}
