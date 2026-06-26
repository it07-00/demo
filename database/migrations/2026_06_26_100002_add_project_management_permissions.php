<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const array PERMISSIONS = [
        'project.create',
        'project.update',
        'project.delete',
    ];

    private const array MANAGER_ROLES = [
        'Super Admin',
        'Giám đốc',
    ];

    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = collect(self::PERMISSIONS)
            ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, 'web'));

        foreach (self::MANAGER_ROLES as $roleName) {
            $role = Role::query()
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first();

            $role?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permissionName) {
            $permission = Permission::query()
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->first();

            if ($permission === null) {
                continue;
            }

            foreach (self::MANAGER_ROLES as $roleName) {
                $role = Role::query()
                    ->where('name', $roleName)
                    ->where('guard_name', 'web')
                    ->first();

                $role?->revokePermissionTo($permission);
            }

            $permission->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
