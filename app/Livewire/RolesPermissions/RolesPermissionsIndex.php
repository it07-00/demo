<?php

declare(strict_types=1);

namespace App\Livewire\RolesPermissions;

use App\DTOs\RoleDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Services\RolePermissionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Quản lý Vai trò & Quyền')]
final class RolesPermissionsIndex extends Component
{
    public int $activeRoleId = 0;

    public string $newRoleName = '';

    public string $newRoleDescription = '';

    public int $editingRoleId = 0;

    public string $editRoleName = '';

    public string $editRoleDescription = '';

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    protected array $rules = [
        'newRoleName' => ['required', 'string', 'max:255', 'unique:roles,name'],
        'newRoleDescription' => ['nullable', 'string', 'max:500'],
        'editRoleName' => ['required', 'string', 'max:255'],
        'editRoleDescription' => ['nullable', 'string', 'max:500'],
    ];

    public function mount(RolePermissionService $service): void
    {
        Gate::authorize('manage', Role::class);

        $firstRole = Role::query()->orderBy('id')->first();
        if ($firstRole) {
            $this->activeRoleId = (int) $firstRole->id;
        }
    }

    public function selectRole(int $roleId): void
    {
        Gate::authorize('manage', Role::class);
        $this->activeRoleId = $roleId;
    }

    public function togglePermission(RolePermissionService $service, string $permissionName): void
    {
        Gate::authorize('manage', Role::class);

        $role = Role::findOrFail($this->activeRoleId);

        if ($role->name === RoleEnum::SuperAdmin->value) {
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Không thể chỉnh sửa!',
                'text' => 'Vai trò Super Admin luôn có tất cả các quyền hạn.',
            ]);

            return;
        }

        $service->togglePermission($role, $permissionName);

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Cập nhật thành công!',
            'text' => 'Đã cập nhật quyền '.$permissionName.' cho vai trò '.$role->name,
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    public function createRole(RolePermissionService $service): void
    {
        Gate::authorize('manage', Role::class);

        $this->validate([
            'newRoleName' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'newRoleDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $dto = RoleDTO::fromArray([
            'name' => $this->newRoleName,
            'description' => $this->newRoleDescription,
        ]);

        $role = $service->createRole($dto);

        $this->activeRoleId = (int) $role->id;
        $this->newRoleName = '';
        $this->newRoleDescription = '';

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Tạo thành công!',
            'text' => 'Đã thêm vai trò mới '.$role->name,
        ]);

        $this->dispatch('role-create:hide');
    }

    public function deleteRole(RolePermissionService $service, int $roleId): void
    {
        Gate::authorize('manage', Role::class);

        $role = Role::findOrFail($roleId);

        if (RoleEnum::isSystemRole($role->name)) {
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Không thể xóa!',
                'text' => 'Vai trò hệ thống không thể bị xóa.',
            ]);

            return;
        }

        $service->deleteRole($role);

        $firstRole = Role::query()->orderBy('id')->first();
        $this->activeRoleId = $firstRole ? (int) $firstRole->id : 0;

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Đã xóa!',
            'text' => 'Đã xóa vai trò '.$role->name.' thành công.',
        ]);
    }

    public function openEditModal(int $roleId): void
    {
        Gate::authorize('manage', Role::class);

        $role = Role::findOrFail($roleId);

        $this->editingRoleId = $roleId;
        $this->editRoleName = $role->name;
        $this->editRoleDescription = $role->description ?? '';
        $this->resetValidation(['editRoleName', 'editRoleDescription']);

        $this->dispatch('role-edit:show');
    }

    public function updateRole(RolePermissionService $service): void
    {
        Gate::authorize('manage', Role::class);

        $this->validate([
            'editRoleName' => ['required', 'string', 'max:255', 'unique:roles,name,'.$this->editingRoleId],
            'editRoleDescription' => ['nullable', 'string', 'max:500'],
        ]);

        $role = Role::findOrFail($this->editingRoleId);

        if (RoleEnum::isSystemRole($role->name)) {
            $this->dispatch('swal:alert', [
                'icon' => 'error',
                'title' => 'Không thể chỉnh sửa!',
                'text' => 'Vai trò hệ thống không thể đổi tên.',
            ]);

            return;
        }

        $dto = RoleDTO::fromArray([
            'name' => $this->editRoleName,
            'description' => $this->editRoleDescription,
        ]);

        $service->updateRole($role, $dto);

        $this->dispatch('role-edit:hide');

        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Cập nhật thành công!',
            'text' => 'Đã cập nhật thông tin vai trò.',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
        ]);
    }

    public function render(RolePermissionService $service): View
    {
        $roles = $service->getRoles();
        $activeRole = $roles->firstWhere('id', $this->activeRoleId) ?? $roles->first();

        $permissionsGrouped = [
            'Quản lý người dùng' => [
                PermissionEnum::UserView->value => 'Xem danh sách người dùng',
                PermissionEnum::UserCreate->value => 'Thêm mới người dùng',
                PermissionEnum::UserUpdate->value => 'Cập nhật người dùng',
                PermissionEnum::UserDelete->value => 'Xóa người dùng',
            ],
            'Lịch công tác' => [
                PermissionEnum::ScheduleView->value => 'Xem lịch công tác',
                PermissionEnum::ScheduleCreate->value => 'Tạo lịch công tác',
                PermissionEnum::ScheduleUpdate->value => 'Cập nhật lịch công tác',
                PermissionEnum::ScheduleDelete->value => 'Xóa lịch công tác',
            ],
            'Báo cáo ngày' => [
                PermissionEnum::ReportView->value => 'Xem báo cáo ngày',
                PermissionEnum::ReportCreate->value => 'Tạo báo cáo ngày',
                PermissionEnum::ReportUpdate->value => 'Sửa báo cáo ngày',
                PermissionEnum::ReportDelete->value => 'Xóa báo cáo ngày',
            ],
            'Quản trị vận hành' => [
                PermissionEnum::ProjectView->value => 'Xem Dự án & Khách hàng',
                PermissionEnum::ProjectCreate->value => 'Tạo dự án',
                PermissionEnum::ProjectUpdate->value => 'Cập nhật dự án',
                PermissionEnum::ProjectDelete->value => 'Xóa dự án',
                PermissionEnum::ReportView->value => 'Xem Báo cáo vận hành',
                PermissionEnum::StaffView->value => 'Xem Nhân sự & Phân công',
                PermissionEnum::AnalyticsView->value => 'Xem KPI & Hiệu suất',
                PermissionEnum::CrmView->value => 'Xem CRM khách hàng',
                PermissionEnum::CrmCreate->value => 'Thêm khách hàng CRM',
                PermissionEnum::CrmUpdate->value => 'Cập nhật khách hàng CRM',
                PermissionEnum::CrmDelete->value => 'Xóa khách hàng CRM',
                PermissionEnum::AlertView->value => 'Xem Cảnh báo vận hành',
            ],
            'Email nội bộ' => [
                PermissionEnum::MailView->value => 'Xem hộp thư nội bộ',
                PermissionEnum::MailSend->value => 'Gửi email nội bộ',
                PermissionEnum::MailUpdate->value => 'Cập nhật cấu hình email',
            ],
            'Quy định tài liệu' => [
                PermissionEnum::DocumentView->value => 'Xem quy định tài liệu',
            ],
            'Cài đặt hệ thống' => [
                PermissionEnum::SettingView->value => 'Xem cài đặt hệ thống',
                PermissionEnum::SettingUpdate->value => 'Cập nhật cài đặt hệ thống',
            ],
            'Bảo mật & Phân quyền' => [
                PermissionEnum::RoleManage->value => 'Quản lý vai trò và phân quyền',
            ],
            'Truy cập chung' => [
                PermissionEnum::DashboardView->value => 'Truy cập Dashboard chính',
            ],
        ];

        $activeRolePermissions = $activeRole ? $activeRole->permissions->pluck('name')->toArray() : [];

        return view('livewire.roles-permissions.roles-permissions-index', [
            'roles' => $roles,
            'activeRole' => $activeRole,
            'permissionsGrouped' => $permissionsGrouped,
            'activeRolePermissions' => $activeRolePermissions,
        ]);
    }
}
