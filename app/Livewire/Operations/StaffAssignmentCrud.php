<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Models\OperationProject;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

final class StaffAssignmentCrud extends Component
{
    public bool $showModal = false;

    public ?string $selectedName = null;

    public ?int $userId = null;

    // User fields
    public string $formRole = '';

    public string $formBranch = '';

    public string $formStatus = 'Chính thức';

    // Checked project IDs
    public array $assignedProjects = [];

    public string $activeTab = 'info'; // 'info' or 'projects'

    protected function rules(): array
    {
        return [
            'formRole' => ['required', 'string', 'in:Quản lý vận hành,Chuyên viên vận hành,None'],
            'formBranch' => ['required_if:formRole,Quản lý vận hành,Chuyên viên vận hành', 'nullable', 'string'],
            'formStatus' => ['required', 'string'],
        ];
    }

    protected array $messages = [
        'formRole.required' => 'Vui lòng chọn vai trò.',
        'formBranch.required_if' => 'Vui lòng nhập/chọn chi nhánh cho nhân sự vận hành.',
        'formStatus.required' => 'Vui lòng chọn trạng thái lao động.',
    ];

    #[On('staff:open-edit')]
    public function openEdit(string $name): void
    {
        $user = User::where('name', $name)->firstOrFail();

        $this->userId = $user->id;
        $this->selectedName = $user->name;
        $this->formRole = $user->operation_role ?? 'None';
        $this->formBranch = $user->operation_branch ?? '';
        $this->formStatus = $user->employment_status ?? 'Chính thức';

        $this->loadAssignments();

        $this->activeTab = 'info';
        $this->showModal = true;
    }

    #[On('staff:open-create')]
    public function openCreate(): void
    {
        $this->userId = null;
        $this->selectedName = null;
        $this->formRole = 'Chuyên viên vận hành';
        $this->formBranch = '';
        $this->formStatus = 'Chính thức';
        $this->assignedProjects = [];

        $this->activeTab = 'info';
        $this->showModal = true;
    }

    public function updatedFormRole(): void
    {
        $this->loadAssignments();
    }

    private function loadAssignments(): void
    {
        if (! $this->selectedName) {
            $this->assignedProjects = [];

            return;
        }

        if ($this->formRole === 'Quản lý vận hành') {
            $this->assignedProjects = OperationProject::where('manager_name', $this->selectedName)
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->toArray();
        } elseif ($this->formRole === 'Chuyên viên vận hành') {
            $projects = OperationProject::all();
            $this->assignedProjects = [];
            foreach ($projects as $project) {
                if (is_array($project->team) && in_array($this->selectedName, $project->team, true)) {
                    $this->assignedProjects[] = (string) $project->id;
                }
            }
        } else {
            $this->assignedProjects = [];
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            $role = $this->formRole === 'None' ? null : $this->formRole;
            $branch = $this->formRole === 'None' ? null : $this->formBranch;

            $user->update([
                'operation_role' => $role,
                'operation_branch' => $branch,
                'employment_status' => $this->formStatus,
            ]);

            $this->saveAssignments($user->name);

            $message = 'Đã cập nhật phân công cho nhân sự '.$user->name;
        } else {
            if (! $this->selectedName) {
                $this->addError('selectedName', 'Vui lòng chọn một nhân viên.');

                return;
            }

            $user = User::where('name', $this->selectedName)->first();
            if (! $user) {
                $this->addError('selectedName', 'Không tìm thấy nhân viên này.');

                return;
            }

            $role = $this->formRole === 'None' ? null : $this->formRole;
            $branch = $this->formRole === 'None' ? null : $this->formBranch;

            $user->update([
                'operation_role' => $role,
                'operation_branch' => $branch,
                'employment_status' => $this->formStatus,
            ]);

            $this->saveAssignments($user->name);

            $message = 'Đã thêm '.$user->name.' vào đội ngũ vận hành.';
        }

        $this->showModal = false;

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => $message,
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);

        $this->dispatch('staff:saved');
    }

    private function saveAssignments(string $name): void
    {
        $projectIds = array_map('intval', $this->assignedProjects);

        if ($this->formRole === 'Quản lý vận hành') {
            // Unassign this manager from projects not checked
            OperationProject::where('manager_name', $name)
                ->whereNotIn('id', $projectIds)
                ->update([
                    'manager_name' => 'Chưa phân công',
                    'manager_external_id' => 'M0',
                    'unassigned' => true,
                ]);

            // Assign this manager to checked projects
            if (count($projectIds) > 0) {
                OperationProject::whereIn('id', $projectIds)
                    ->update([
                        'manager_name' => $name,
                        'manager_external_id' => 'M'.$this->userId,
                        'unassigned' => false,
                    ]);
            }
        } elseif ($this->formRole === 'Chuyên viên vận hành') {
            $allProjects = OperationProject::all();

            foreach ($allProjects as $project) {
                $team = is_array($project->team) ? $project->team : [];
                $inAssigned = in_array($project->id, $projectIds, true);
                $inTeam = in_array($name, $team, true);

                if ($inAssigned && ! $inTeam) {
                    $team[] = $name;
                    $project->update(['team' => array_values($team)]);
                } elseif (! $inAssigned && $inTeam) {
                    $team = array_filter($team, fn ($t) => $t !== $name);
                    $project->update(['team' => array_values($team)]);
                }
            }
        }
    }

    public function render(): View
    {
        $branches = ['Bắc Ninh', 'Bắc Giang', 'Hà Nam', 'Nam Định', 'Đà Nẵng', 'Nghệ An', 'Vĩnh Phúc'];

        // Get all users who can be assigned (excluding already assigned if creating, or all for dropdown)
        $users = User::orderBy('name')->get();

        $projects = OperationProject::orderBy('code')->get();

        return view('livewire.operations.staff-assignment-crud', [
            'users' => $users,
            'branches' => $branches,
            'projects' => $projects,
        ]);
    }
}
