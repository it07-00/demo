<?php

declare(strict_types=1);

namespace App\Livewire\Operations;

use App\Enums\PermissionEnum;
use App\Models\OperationProject;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

final class ProjectCrud extends Component
{
    use WithFileUploads;

    public bool $showModal = false;

    public int $editingId = 0;

    // Form fields
    public string $formCode = '';

    public string $formName = '';

    public string $formCustomer = '';

    public string $formCustomerType = 'Thông thường';

    public string $formBranch = '';

    public string $formProduct = '';

    public string $formMethod = '';

    public string $formPolicy = '';

    public int $formUnitPrice = 30;

    public string $formRecruitStatus = 'Đang tuyển';

    public string $formManagerName = '';

    public string $formManagerExternalId = '';

    public string $formStatus = 'Đang vận hành';

    public int $formDemand = 0;

    public int $formActual = 0;

    public int $formProgress = 0;

    public string $formContractStart = '';

    public string $formContractEnd = '';

    /** @var list<string> */
    public array $formTeam = [];

    // File upload properties
    public $formDocFile;

    public string $formDocType = 'Hợp đồng';

    public array $formDocs = [];

    protected function rules(): array
    {
        return [
            'formCode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('operation_projects', 'code')->ignore($this->editingId ?: null),
            ],
            'formName' => ['required', 'string', 'max:255'],
            'formCustomer' => ['required', 'string', 'max:255'],
            'formCustomerType' => ['required', 'in:Trọng điểm,Thông thường'],
            'formBranch' => ['required', 'string', 'max:255'],
            'formProduct' => ['required', 'string', 'max:255'],
            'formMethod' => ['required', 'string', 'max:255'],
            'formPolicy' => ['required', 'string', 'max:255'],
            'formUnitPrice' => ['required', 'integer', 'min:1'],
            'formStatus' => ['required', 'string'],
            'formDemand' => ['required', 'integer', 'min:0'],
            'formActual' => ['required', 'integer', 'min:0'],
            'formProgress' => ['required', 'integer', 'min:0', 'max:21'],
            'formContractStart' => ['required', 'date'],
            'formContractEnd' => ['required', 'date', 'after_or_equal:formContractStart'],
        ];
    }

    protected array $messages = [
        'formCode.required' => 'Vui lòng nhập mã dự án.',
        'formCode.unique' => 'Mã dự án đã tồn tại.',
        'formName.required' => 'Vui lòng nhập tên dự án.',
        'formCustomer.required' => 'Vui lòng nhập tên khách hàng.',
        'formBranch.required' => 'Vui lòng chọn chi nhánh.',
        'formDemand.required' => 'Vui lòng nhập nhu cầu.',
        'formContractStart.required' => 'Vui lòng chọn ngày bắt đầu HĐ.',
        'formContractEnd.required' => 'Vui lòng chọn ngày kết thúc HĐ.',
        'formContractEnd.after_or_equal' => 'Ngày kết thúc phải sau ngày bắt đầu.',
    ];

    #[On('project:open-create')]
    public function openCreate(): void
    {
        Gate::authorize(PermissionEnum::ProjectCreate->value);

        $this->resetForm();
        $this->editingId = 0;
        $this->formContractStart = now()->toDateString();
        $this->formContractEnd = now()->addMonths(6)->toDateString();
        $this->formDocs = [];
        $this->formDocFile = null;
        $this->showModal = true;
    }

    #[On('project:open-edit')]
    public function openEdit(int $id): void
    {
        Gate::authorize(PermissionEnum::ProjectUpdate->value);

        $project = OperationProject::findOrFail($id);

        $this->editingId = $id;
        $this->formCode = $project->code;
        $this->formName = $project->name;
        $this->formCustomer = $project->customer;
        $this->formCustomerType = $project->customer_type;
        $this->formBranch = $project->branch;
        $this->formProduct = $project->product;
        $this->formMethod = $project->method;
        $this->formPolicy = $project->policy;
        $this->formUnitPrice = (int) $project->unit_price;
        $this->formRecruitStatus = $project->recruit_status;
        $this->formManagerName = $project->manager_name;
        $this->formManagerExternalId = $project->manager_external_id;
        $this->formStatus = $project->status;
        $this->formDemand = (int) $project->demand;
        $this->formActual = (int) $project->actual;
        $this->formProgress = (int) $project->progress;
        $this->formContractStart = $project->contract_start->toDateString();
        $this->formContractEnd = $project->contract_end->toDateString();
        $this->formTeam = $project->team ?? [];
        $this->formDocs = $project->docs ?? [];
        $this->formDocFile = null;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        Gate::authorize($this->editingId > 0 ? PermissionEnum::ProjectUpdate->value : PermissionEnum::ProjectCreate->value);

        $this->validate();

        $data = [
            'code' => $this->formCode,
            'name' => $this->formName,
            'customer' => $this->formCustomer,
            'customer_type' => $this->formCustomerType,
            'branch' => $this->formBranch,
            'product' => $this->formProduct,
            'method' => $this->formMethod,
            'policy' => $this->formPolicy,
            'unit_price' => $this->formUnitPrice,
            'recruit_status' => $this->formRecruitStatus,
            'manager_name' => $this->formManagerName,
            'manager_external_id' => $this->formManagerExternalId ?: 'M0',
            'status' => $this->formStatus,
            'demand' => $this->formDemand,
            'actual' => $this->formActual,
            'shortage' => max(0, $this->formDemand - $this->formActual),
            'progress' => $this->formProgress,
            'contract_start' => $this->formContractStart,
            'contract_end' => $this->formContractEnd,
            'team' => $this->formTeam,
            'docs' => $this->formDocs,
        ];

        if ($this->editingId > 0) {
            $project = OperationProject::findOrFail($this->editingId);
            $project->update($data);
            $message = 'Đã cập nhật dự án '.$this->formCode;
        } else {
            $data['external_id'] = 'P'.str_pad((string) (OperationProject::max('id') + 1), 3, '0', STR_PAD_LEFT);
            OperationProject::create($data);
            $message = 'Đã tạo dự án '.$this->formCode;
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
        $this->dispatch('project:saved');
    }

    #[On('project:delete')]
    public function delete(int $id): void
    {
        Gate::authorize(PermissionEnum::ProjectDelete->value);

        $project = OperationProject::findOrFail($id);
        $code = $project->code;
        $project->delete();

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Dự án '.$code.' đã bị xóa.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
        $this->dispatch('project:saved');
    }

    public function addTeamMember(): void
    {
        $this->formTeam[] = '';
    }

    public function removeTeamMember(int $index): void
    {
        unset($this->formTeam[$index]);
        $this->formTeam = array_values($this->formTeam);
    }

    private function resetForm(): void
    {
        $this->formCode = '';
        $this->formName = '';
        $this->formCustomer = '';
        $this->formCustomerType = 'Thông thường';
        $this->formBranch = '';
        $this->formProduct = '';
        $this->formMethod = 'Cộng tác viên';
        $this->formPolicy = 'Lương cứng + tăng ca';
        $this->formUnitPrice = 30;
        $this->formRecruitStatus = 'Đang tuyển';
        $this->formManagerName = '';
        $this->formManagerExternalId = '';
        $this->formStatus = 'Đang vận hành';
        $this->formDemand = 0;
        $this->formActual = 0;
        $this->formProgress = 0;
        $this->formTeam = [];
        $this->formDocs = [];
        $this->formDocFile = null;
        $this->resetValidation();
    }

    public function addDocument(): void
    {
        Gate::authorize($this->editingId > 0 ? PermissionEnum::ProjectUpdate->value : PermissionEnum::ProjectCreate->value);

        $this->validate([
            'formDocFile' => 'required|file|max:10240', // Max 10MB
            'formDocType' => 'required|string',
        ], [
            'formDocFile.required' => 'Vui lòng chọn file.',
            'formDocFile.max' => 'Dung lượng file tối đa là 10MB.',
        ]);

        $path = $this->formDocFile->store('projects/documents', 'public');

        $this->formDocs[] = [
            'name' => $this->formDocFile->getClientOriginalName(),
            'type' => $this->formDocType,
            'path' => $path,
        ];

        $this->formDocFile = null;

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã tải lên tài liệu!',
            'text' => 'Bấm "Cập nhật"/"Tạo dự án" để lưu cấu hình.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    public function removeDocument(int $index): void
    {
        unset($this->formDocs[$index]);
        $this->formDocs = array_values($this->formDocs);
    }

    public function render(): View
    {
        $branches = User::query()
            ->whereNotNull('operation_branch')
            ->distinct()
            ->pluck('operation_branch')
            ->sort()
            ->values()
            ->toArray();

        if ($branches === []) {
            $branches = ['Bắc Ninh', 'Bắc Giang', 'Hà Nam', 'Nam Định', 'Đà Nẵng', 'Nghệ An', 'Vĩnh Phúc'];
        }

        $managers = User::query()
            ->where('operation_role', 'Quản lý vận hành')
            ->orderBy('name')
            ->get(['id', 'name']);

        $specialists = User::query()
            ->where('operation_role', 'Chuyên viên vận hành')
            ->orderBy('name')
            ->get(['id', 'name']);

        $methods = ['Cộng tác viên', 'Tự tuyển', 'Đối tác cung ứng', 'Quảng cáo tuyển', 'Giới thiệu nội bộ'];
        $products = ['Cung ứng LĐ thời vụ', 'Cung ứng LĐ chính thức', 'Khoán việc dây chuyền', 'Vệ sinh công nghiệp', 'Outsourcing kho vận'];
        $policies = ['Lương cứng + tăng ca', 'Khoán sản phẩm', 'Lương cứng + KPI', 'Hỗ trợ ở + ăn ca'];

        return view('livewire.operations.project-crud', [
            'branches' => $branches,
            'managers' => $managers,
            'specialists' => $specialists,
            'methods' => $methods,
            'products' => $products,
            'policies' => $policies,
        ]);
    }
}
