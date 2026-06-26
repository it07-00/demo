<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\RoleDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class RolePermissionService
{
    /**
     * @var array<string, string>
     */
    private const array REQUIRED_PARENT_PERMISSIONS = [
        PermissionEnum::UserView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::UserCreate->value => PermissionEnum::UserView->value,
        PermissionEnum::UserUpdate->value => PermissionEnum::UserView->value,
        PermissionEnum::UserDelete->value => PermissionEnum::UserView->value,
        PermissionEnum::ScheduleView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::ScheduleCreate->value => PermissionEnum::ScheduleView->value,
        PermissionEnum::ScheduleUpdate->value => PermissionEnum::ScheduleView->value,
        PermissionEnum::ScheduleDelete->value => PermissionEnum::ScheduleView->value,
        PermissionEnum::ReportView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::ReportCreate->value => PermissionEnum::ReportView->value,
        PermissionEnum::ReportUpdate->value => PermissionEnum::ReportView->value,
        PermissionEnum::ReportDelete->value => PermissionEnum::ReportView->value,
        PermissionEnum::MailView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::MailSend->value => PermissionEnum::MailView->value,
        PermissionEnum::MailUpdate->value => PermissionEnum::MailView->value,
        PermissionEnum::DocumentView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::ProjectView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::StaffView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::AnalyticsView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::CrmView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::CrmCreate->value => PermissionEnum::CrmView->value,
        PermissionEnum::CrmUpdate->value => PermissionEnum::CrmView->value,
        PermissionEnum::CrmDelete->value => PermissionEnum::CrmView->value,
        PermissionEnum::AlertView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::SettingView->value => PermissionEnum::DashboardView->value,
        PermissionEnum::SettingUpdate->value => PermissionEnum::SettingView->value,
        PermissionEnum::RoleManage->value => PermissionEnum::DashboardView->value,
    ];

    /**
     * @return Collection<int, Role>
     */
    public function getRoles(): Collection
    {
        return Role::query()
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function getPermissions(): Collection
    {
        return Permission::query()
            ->orderBy('id')
            ->get();
    }

    public function createRole(RoleDTO $dto): Role
    {
        return DB::transaction(function () use ($dto): Role {
            return Role::create([
                'name' => $dto->name,
                'description' => $dto->description,
                'guard_name' => 'web',
            ]);
        });
    }

    public function updateRole(Role $role, RoleDTO $dto): Role
    {
        return DB::transaction(function () use ($role, $dto): Role {
            if (RoleEnum::isSystemRole($role->name)) {
                throw new \InvalidArgumentException('Không thể đổi tên vai trò hệ thống');
            }

            $role->update([
                'name' => $dto->name,
                'description' => $dto->description,
            ]);

            return $role->refresh();
        });
    }

    public function deleteRole(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            if (RoleEnum::isSystemRole($role->name)) {
                throw new \InvalidArgumentException('Không thể xóa vai trò hệ thống');
            }

            $role->delete();
        });
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    public function syncPermissions(Role $role, array $permissionNames): void
    {
        DB::transaction(function () use ($role, $permissionNames): void {
            if ($role->name === RoleEnum::SuperAdmin->value) {
                $role->syncPermissions(Permission::all());

                return;
            }

            $role->syncPermissions($this->normalizePermissionNames($permissionNames));
        });
    }

    public function togglePermission(Role $role, string $permissionName): void
    {
        $permissions = $role->permissions->pluck('name')->all();

        if (in_array($permissionName, $permissions, true)) {
            $permissions = $this->removePermissionAndDependents($permissions, $permissionName);
        } else {
            $permissions[] = $permissionName;
        }

        $this->syncPermissions($role, $permissions);
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return list<string>
     */
    private function removePermissionAndDependents(array $permissionNames, string $permissionName): array
    {
        $permissions = array_values(array_diff($permissionNames, [$permissionName]));

        do {
            $changed = false;

            foreach (self::REQUIRED_PARENT_PERMISSIONS as $child => $parent) {
                if ($parent === $permissionName || (! in_array($parent, $permissions, true) && in_array($child, $permissions, true))) {
                    $before = $permissions;
                    $permissions = array_values(array_diff($permissions, [$child]));
                    $changed = $changed || $before !== $permissions;
                }
            }
        } while ($changed);

        return $permissions;
    }

    /**
     * @param  array<int, string>  $permissionNames
     * @return list<string>
     */
    private function normalizePermissionNames(array $permissionNames): array
    {
        $permissions = array_values(array_unique($permissionNames));

        do {
            $changed = false;

            foreach (self::REQUIRED_PARENT_PERMISSIONS as $child => $parent) {
                if (in_array($child, $permissions, true) && ! in_array($parent, $permissions, true)) {
                    $permissions[] = $parent;
                    $changed = true;
                }
            }
        } while ($changed);

        do {
            $changed = false;

            foreach (self::REQUIRED_PARENT_PERMISSIONS as $child => $parent) {
                if (! in_array($parent, $permissions, true) && in_array($child, $permissions, true)) {
                    $permissions = array_values(array_diff($permissions, [$child]));
                    $changed = true;
                }
            }
        } while ($changed);

        return array_values(array_unique($permissions));
    }
}
