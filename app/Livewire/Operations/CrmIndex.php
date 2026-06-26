<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Enums\PermissionEnum;
use App\Models\OperationCrmCustomer;
use App\Models\OperationProject;
use App\Models\OperationReceivable;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('CRM khách hàng')]
final class CrmIndex extends Component
{
    private const array STAGES = [
        'Đang trao đổi',
        'Ăn cafe',
        'Ký hợp đồng dài hạn',
        'Duy trì & mở rộng',
    ];

    private const array RELATIONSHIPS = [
        'Rất tốt',
        'Tốt',
        'Bình thường',
        'Cần chăm sóc',
    ];

    private const array PRIORITIES = [
        'Cao',
        'Bình thường',
        'Theo dõi',
    ];

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $stage = '';

    #[Url(except: '')]
    public string $relationship = '';

    #[Url(except: '')]
    public string $priority = '';

    #[Url(except: 'all')]
    public string $active = 'all';

    public ?int $editingId = null;

    public string $name = '';

    public string $type = 'Khách hàng trọng điểm';

    public int $stage_idx = 0;

    public string $relationshipField = 'Tốt';

    public string $contact_name = '';

    public string $contact_role = '';

    public string $contact_phone = '';

    public string $contact_email = '';

    public string $source = '';

    public string $priorityField = 'Bình thường';

    public string $owner_name = '';

    public int $revenue_monthly = 0;

    public string $last_meeting = '';

    public string $next_meeting = '';

    public string $next_action = '';

    public bool $activeField = true;

    public string $notesText = '';

    public function mount(): void
    {
        Gate::authorize(PermissionEnum::CrmView->value);

        $today = now()->toDateString();
        $this->last_meeting = $today;
        $this->next_meeting = now()->addWeek()->toDateString();
    }

    public function updatedSearch(): void
    {
        $this->resetValidation();
    }

    public function create(): void
    {
        Gate::authorize(PermissionEnum::CrmCreate->value);

        $this->resetForm();
        $this->dispatch('crm-form:show');
    }

    public function edit(int $customerId): void
    {
        Gate::authorize(PermissionEnum::CrmUpdate->value);

        $customer = OperationCrmCustomer::query()->findOrFail($customerId);

        $this->editingId = $customer->id;
        $this->name = $customer->name;
        $this->type = $customer->type;
        $this->stage_idx = (int) $customer->stage_idx;
        $this->relationshipField = $this->normalizeRelationship($customer->relationship);
        $this->contact_name = $customer->contact_name;
        $this->contact_role = $customer->contact_role;
        $this->contact_phone = (string) $customer->contact_phone;
        $this->contact_email = (string) $customer->contact_email;
        $this->source = (string) $customer->source;
        $this->priorityField = in_array($customer->priority, self::PRIORITIES, true) ? $customer->priority : 'Bình thường';
        $this->owner_name = (string) $customer->owner_name;
        $this->revenue_monthly = (int) $customer->revenue_monthly;
        $this->last_meeting = $customer->last_meeting?->toDateString() ?? now()->toDateString();
        $this->next_meeting = $customer->next_meeting?->toDateString() ?? now()->addWeek()->toDateString();
        $this->next_action = (string) $customer->next_action;
        $this->activeField = (bool) $customer->active;
        $this->notesText = implode(PHP_EOL, $customer->notes ?? []);

        $this->dispatch('crm-form:show');
    }

    public function save(): void
    {
        Gate::authorize($this->editingId ? PermissionEnum::CrmUpdate->value : PermissionEnum::CrmCreate->value);

        $validated = $this->validate($this->rules());
        $stageIndex = (int) $validated['stage_idx'];

        OperationCrmCustomer::query()->updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => trim((string) $validated['name']),
                'type' => trim((string) $validated['type']),
                'stage' => self::STAGES[$stageIndex],
                'stage_idx' => $stageIndex,
                'relationship' => $validated['relationshipField'],
                'contact_name' => trim((string) $validated['contact_name']),
                'contact_role' => trim((string) $validated['contact_role']),
                'contact_phone' => $this->blankToNull($validated['contact_phone']),
                'contact_email' => $this->blankToNull($validated['contact_email']),
                'source' => $this->blankToNull($validated['source']),
                'priority' => $validated['priorityField'],
                'owner_name' => $this->blankToNull($validated['owner_name']),
                'revenue_monthly' => (int) $validated['revenue_monthly'],
                'last_meeting' => $validated['last_meeting'],
                'next_meeting' => $validated['next_meeting'],
                'next_action' => $this->blankToNull($validated['next_action']),
                'active' => (bool) $validated['activeField'],
                'notes' => $this->parseNotes($validated['notesText']),
            ],
        );

        $this->dispatch('crm-form:hide');
        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã lưu hồ sơ CRM',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2500,
        ]);

        $this->resetForm();
    }

    public function moveStage(int $customerId, int $stageIndex): void
    {
        Gate::authorize(PermissionEnum::CrmUpdate->value);

        if (! array_key_exists($stageIndex, self::STAGES)) {
            return;
        }

        OperationCrmCustomer::query()
            ->whereKey($customerId)
            ->update([
                'stage_idx' => $stageIndex,
                'stage' => self::STAGES[$stageIndex],
            ]);

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã cập nhật giai đoạn',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    public function toggleActive(int $customerId): void
    {
        Gate::authorize(PermissionEnum::CrmUpdate->value);

        $customer = OperationCrmCustomer::query()->findOrFail($customerId);
        $customer->update(['active' => ! $customer->active]);
    }

    public function delete(int $customerId): void
    {
        Gate::authorize(PermissionEnum::CrmDelete->value);

        OperationCrmCustomer::query()->whereKey($customerId)->delete();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa hồ sơ khách hàng',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 2000,
        ]);
    }

    public function render(): View
    {
        $today = CarbonImmutable::instance(now()->startOfDay());
        $customers = $this->customers();
        $projects = OperationProject::query()->get(['code', 'name', 'customer', 'branch', 'status', 'demand', 'actual']);
        $receivables = OperationReceivable::query()->where('paid', false)->get(['customer', 'amount', 'due_date', 'note', 'state']);
        $customersWithContext = $customers->map(fn (OperationCrmCustomer $customer): array => $this->customerViewModel($customer, $projects, $receivables, $today));
        $byStage = [];

        foreach (self::STAGES as $index => $stageName) {
            $byStage[$stageName] = $customersWithContext
                ->filter(static fn (array $customer): bool => $customer['stage_idx'] === $index)
                ->values()
                ->all();
        }

        return view('livewire.operations.crm-index', [
            'customers' => $customersWithContext,
            'customersByStage' => $byStage,
            'stages' => self::STAGES,
            'relationships' => self::RELATIONSHIPS,
            'priorities' => self::PRIORITIES,
            'owners' => $this->ownerOptions(),
            'summary' => $this->summary($customersWithContext),
            'today' => $today,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('operation_crm_customers', 'name')->ignore($this->editingId)],
            'type' => ['required', 'string', 'max:255'],
            'stage_idx' => ['required', 'integer', Rule::in(array_keys(self::STAGES))],
            'relationshipField' => ['required', Rule::in(self::RELATIONSHIPS)],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_role' => ['required', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:255'],
            'priorityField' => ['required', Rule::in(self::PRIORITIES)],
            'owner_name' => ['nullable', 'string', 'max:255'],
            'revenue_monthly' => ['required', 'integer', 'min:0'],
            'last_meeting' => ['required', 'date'],
            'next_meeting' => ['required', 'date', 'after_or_equal:last_meeting'],
            'next_action' => ['nullable', 'string', 'max:255'],
            'activeField' => ['boolean'],
            'notesText' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return Collection<int, OperationCrmCustomer>
     */
    private function customers(): Collection
    {
        $query = OperationCrmCustomer::query()
            ->orderBy('stage_idx')
            ->orderByDesc('priority')
            ->orderBy('next_meeting')
            ->orderBy('name');

        if ($this->search !== '') {
            $search = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $this->search).'%';
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('name', 'like', $search)
                    ->orWhere('contact_name', 'like', $search)
                    ->orWhere('contact_role', 'like', $search)
                    ->orWhere('owner_name', 'like', $search);
            });
        }

        if ($this->stage !== '' && is_numeric($this->stage)) {
            $query->where('stage_idx', (int) $this->stage);
        }

        if ($this->relationship !== '') {
            $query->where('relationship', $this->relationship);
        }

        if ($this->priority !== '') {
            $query->where('priority', $this->priority);
        }

        if ($this->active !== 'all') {
            $query->where('active', $this->active === 'active');
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, OperationProject>  $projects
     * @param  Collection<int, OperationReceivable>  $receivables
     * @return array<string, mixed>
     */
    private function customerViewModel(OperationCrmCustomer $customer, Collection $projects, Collection $receivables, CarbonImmutable $today): array
    {
        $customerProjects = $projects->where('customer', $customer->name)->values();
        $customerReceivables = $receivables->where('customer', $customer->name)->values();
        $nextMeeting = CarbonImmutable::instance($customer->next_meeting);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'type' => $customer->type,
            'stage' => self::STAGES[(int) $customer->stage_idx] ?? $customer->stage,
            'stage_idx' => (int) $customer->stage_idx,
            'relationship' => $this->normalizeRelationship($customer->relationship),
            'contact_name' => $customer->contact_name,
            'contact_role' => $customer->contact_role,
            'contact_phone' => $customer->contact_phone,
            'contact_email' => $customer->contact_email,
            'source' => $customer->source,
            'priority' => in_array($customer->priority, self::PRIORITIES, true) ? $customer->priority : 'Bình thường',
            'owner_name' => $customer->owner_name,
            'revenue_monthly' => (int) $customer->revenue_monthly,
            'last_meeting' => CarbonImmutable::instance($customer->last_meeting),
            'next_meeting' => $nextMeeting,
            'days_to_meeting' => $today->diffInDays($nextMeeting, false),
            'next_action' => $customer->next_action,
            'active' => (bool) $customer->active,
            'notes' => $customer->notes ?? [],
            'projects' => $customerProjects,
            'project_count' => $customerProjects->count(),
            'branches' => $customerProjects->pluck('branch')->unique()->values()->all(),
            'receivables' => $customerReceivables,
            'receivable_total' => $customerReceivables->sum('amount'),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $customers
     * @return array<string, int>
     */
    private function summary(\Illuminate\Support\Collection $customers): array
    {
        return [
            'total' => $customers->count(),
            'active' => $customers->where('active', true)->count(),
            'signed' => $customers->where('stage_idx', '>=', 2)->count(),
            'due_soon' => $customers->filter(static fn (array $customer): bool => $customer['days_to_meeting'] <= 3)->count(),
            'revenue' => $customers->sum('revenue_monthly'),
            'receivable' => $customers->sum('receivable_total'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function ownerOptions(): array
    {
        return OperationCrmCustomer::query()
            ->whereNotNull('owner_name')
            ->where('owner_name', '<>', '')
            ->orderBy('owner_name')
            ->pluck('owner_name')
            ->unique()
            ->values()
            ->all();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->name = '';
        $this->type = 'Khách hàng trọng điểm';
        $this->stage_idx = 0;
        $this->relationshipField = 'Tốt';
        $this->contact_name = '';
        $this->contact_role = '';
        $this->contact_phone = '';
        $this->contact_email = '';
        $this->source = '';
        $this->priorityField = 'Bình thường';
        $this->owner_name = '';
        $this->revenue_monthly = 0;
        $this->last_meeting = now()->toDateString();
        $this->next_meeting = now()->addWeek()->toDateString();
        $this->next_action = '';
        $this->activeField = true;
        $this->notesText = '';
        $this->resetValidation();
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private function parseNotes(?string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', (string) $value) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeRelationship(string $relationship): string
    {
        return match ($relationship) {
            'Ráº¥t tá»‘t' => 'Rất tốt',
            'Tá»‘t' => 'Tốt',
            'BÃ¬nh thÆ°á»ng' => 'Bình thường',
            default => in_array($relationship, self::RELATIONSHIPS, true) ? $relationship : 'Cần chăm sóc',
        };
    }
}
