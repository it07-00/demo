<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Models\OperationProject;
use App\Models\OperationRecruitmentReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

final class RecruitmentReportCrud extends Component
{
    public bool $showModal = false;

    public int $editingId = 0;

    // Form fields
    public int $formProjectId = 0;

    public string $formDate = '';

    public string $formBranch = '';

    public string $formCustomer = '';

    public string $formManager = '';

    public int $formDemand = 0;

    public string $formMethod = '';

    public int $formRegistered = 0;

    public int $formInterviewed = 0;

    public int $formPassed = 0;

    public int $formStarted = 0;

    public int $formPartnerTrial = 0;

    public string $formRank = 'A';

    public string $formReporter = '';

    public string $formIssues = '';

    protected function rules(): array
    {
        return [
            'formProjectId' => ['required', 'exists:operation_projects,id'],
            'formDate' => ['required', 'date'],
            'formRegistered' => ['required', 'integer', 'min:0'],
            'formInterviewed' => ['required', 'integer', 'min:0'],
            'formPassed' => ['required', 'integer', 'min:0'],
            'formStarted' => ['required', 'integer', 'min:0'],
            'formPartnerTrial' => ['required', 'integer', 'min:0'],
            'formRank' => ['required', 'in:A,B,C'],
        ];
    }

    protected array $messages = [
        'formProjectId.required' => 'Vui lòng chọn dự án.',
        'formDate.required' => 'Vui lòng chọn ngày.',
        'formRegistered.required' => 'Vui lòng nhập số đăng ký.',
    ];

    #[On('report:open-create')]
    public function openCreate(): void
    {
        $this->resetForm();
        $this->formDate = now()->toDateString();
        $this->formReporter = Auth::user()?->name ?? '';
        $this->showModal = true;
    }

    #[On('report:open-edit')]
    public function openEdit(int $id): void
    {
        $report = OperationRecruitmentReport::findOrFail($id);

        $this->editingId = $id;
        $this->formProjectId = $report->operation_project_id;
        $this->formDate = $report->report_date->toDateString();
        $this->formBranch = $report->branch;
        $this->formCustomer = $report->customer;
        $this->formManager = $report->manager;
        $this->formDemand = (int) $report->demand;
        $this->formMethod = $report->method;
        $this->formRegistered = (int) $report->registered;
        $this->formInterviewed = (int) $report->interviewed;
        $this->formPassed = (int) $report->passed;
        $this->formStarted = (int) $report->started;
        $this->formPartnerTrial = (int) $report->partner_trial;
        $this->formRank = $report->rank;
        $this->formReporter = $report->reporter;
        $this->formIssues = $report->issues ?? '';

        $this->resetValidation();
        $this->showModal = true;
    }

    public function updatedFormProjectId(): void
    {
        if ($this->formProjectId > 0) {
            $project = OperationProject::find($this->formProjectId);
            if ($project) {
                $this->formBranch = $project->branch;
                $this->formCustomer = $project->customer;
                $this->formManager = $project->manager_name;
                $this->formDemand = (int) $project->demand;
                $this->formMethod = $project->method;
            }
        }
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'operation_project_id' => $this->formProjectId,
            'report_date' => $this->formDate,
            'branch' => $this->formBranch,
            'customer' => $this->formCustomer,
            'manager' => $this->formManager,
            'demand' => $this->formDemand,
            'method' => $this->formMethod,
            'registered' => $this->formRegistered,
            'interviewed' => $this->formInterviewed,
            'passed' => $this->formPassed,
            'started' => $this->formStarted,
            'partner_trial' => $this->formPartnerTrial,
            'rank' => $this->formRank,
            'reporter' => $this->formReporter,
            'reported_at' => now()->format('H:i'),
            'issues' => $this->formIssues ?: null,
            'approved' => false,
        ];

        if ($this->editingId > 0) {
            $report = OperationRecruitmentReport::findOrFail($this->editingId);
            $report->update($data);
            $message = 'Đã cập nhật báo cáo tuyển dụng.';
        } else {
            OperationRecruitmentReport::create($data);
            $message = 'Đã tạo báo cáo tuyển dụng ngày ' . $this->formDate;
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
        $this->dispatch('report:saved');
    }

    #[On('report:approve')]
    public function approve(int $id): void
    {
        $report = OperationRecruitmentReport::findOrFail($id);
        $report->update(['approved' => true]);

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã duyệt!',
            'text' => 'Báo cáo đã được phê duyệt.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
        $this->dispatch('report:saved');
    }

    #[On('report:delete')]
    public function delete(int $id): void
    {
        OperationRecruitmentReport::findOrFail($id)->delete();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Báo cáo tuyển dụng đã bị xóa.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
        $this->dispatch('report:saved');
    }

    private function resetForm(): void
    {
        $this->editingId = 0;
        $this->formProjectId = 0;
        $this->formDate = '';
        $this->formBranch = '';
        $this->formCustomer = '';
        $this->formManager = '';
        $this->formDemand = 0;
        $this->formMethod = '';
        $this->formRegistered = 0;
        $this->formInterviewed = 0;
        $this->formPassed = 0;
        $this->formStarted = 0;
        $this->formPartnerTrial = 0;
        $this->formRank = 'A';
        $this->formReporter = '';
        $this->formIssues = '';
        $this->resetValidation();
    }

    public function render(): View
    {
        $projects = OperationProject::query()
            ->where('status', 'Đang vận hành')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'customer', 'branch', 'manager_name', 'demand', 'method']);

        return view('livewire.operations.recruitment-report-crud', [
            'projects' => $projects,
        ]);
    }
}
