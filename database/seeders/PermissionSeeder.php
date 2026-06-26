<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        $this->normalizeLegacyRoleNames();

        $superAdmin = Role::findOrCreate(RoleEnum::SuperAdmin->value, 'web');
        $superAdmin->update(['description' => 'Quyền quản trị tối cao, kiểm soát toàn bộ hệ thống']);

        $director = Role::findOrCreate(RoleEnum::Director->value, 'web');
        $director->update(['description' => 'Ban Giám đốc, xem báo cáo tổng quan và duyệt lịch trình']);

        $it = Role::findOrCreate(RoleEnum::IT->value, 'web');
        $it->update(['description' => 'Phòng kỹ thuật, bảo trì hệ thống và hỗ trợ người dùng']);

        $sales = Role::findOrCreate(RoleEnum::Sales->value, 'web');
        $sales->update(['description' => 'Phòng kinh doanh, chăm sóc khách hàng và bán hàng']);

        $consultant = Role::findOrCreate(RoleEnum::Consultant->value, 'web');
        $consultant->update(['description' => 'Bộ phận tư vấn, hỗ trợ nghiệp vụ và hướng dẫn nghiệp vụ']);

        $accountant = Role::findOrCreate(RoleEnum::Accountant->value, 'web');
        $accountant->update(['description' => 'Bộ phận kế toán, quản lý tài chính và bảng lương']);

        $superAdmin->syncPermissions(Permission::all());

        $directorPermissions = [
            PermissionEnum::DashboardView->value,
            PermissionEnum::ProjectView->value,
            PermissionEnum::ProjectCreate->value,
            PermissionEnum::ProjectUpdate->value,
            PermissionEnum::ProjectDelete->value,
            PermissionEnum::StaffView->value,
            PermissionEnum::AnalyticsView->value,
            PermissionEnum::CrmView->value,
            PermissionEnum::CrmCreate->value,
            PermissionEnum::CrmUpdate->value,
            PermissionEnum::CrmDelete->value,
            PermissionEnum::AlertView->value,
            PermissionEnum::ScheduleView->value,
            PermissionEnum::ScheduleCreate->value,
            PermissionEnum::ScheduleUpdate->value,
            PermissionEnum::ScheduleDelete->value,
            PermissionEnum::ReportView->value,
            PermissionEnum::DocumentView->value,
            PermissionEnum::MailView->value,
            PermissionEnum::MailSend->value,
            PermissionEnum::MailUpdate->value,
            PermissionEnum::WorkProgressView->value,
            PermissionEnum::WorkProgressManage->value,
        ];

        $staffPermissions = [
            PermissionEnum::DashboardView->value,
            PermissionEnum::ProjectView->value,
            PermissionEnum::AlertView->value,
            PermissionEnum::ScheduleView->value,
            PermissionEnum::ScheduleCreate->value,
            PermissionEnum::ScheduleUpdate->value,
            PermissionEnum::ScheduleDelete->value,
            PermissionEnum::ReportView->value,
            PermissionEnum::ReportCreate->value,
            PermissionEnum::ReportUpdate->value,
            PermissionEnum::DocumentView->value,
            PermissionEnum::MailView->value,
            PermissionEnum::MailSend->value,
            PermissionEnum::MailUpdate->value,
            PermissionEnum::WorkProgressView->value,
            PermissionEnum::WorkProgressCreate->value,
            PermissionEnum::WorkProgressUpdate->value,
            PermissionEnum::WorkProgressManage->value,
        ];

        $director->syncPermissions($directorPermissions);
        $it->syncPermissions($staffPermissions);
        $sales->syncPermissions($staffPermissions);
        $consultant->syncPermissions($staffPermissions);
        $accountant->syncPermissions($staffPermissions);

        $user = User::query()->firstOrCreate(
            ['username' => 'superadmin'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
            ],
        );

        $user->assignRole(RoleEnum::SuperAdmin->value);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function normalizeLegacyRoleNames(): void
    {
        $legacyNames = [
            'GiÃ¡m Ä‘á»‘c' => RoleEnum::Director->value,
            'PhÃ²ng Kinh doanh' => RoleEnum::Sales->value,
            'TÆ° váº¥n' => RoleEnum::Consultant->value,
            'Káº¿ toÃ¡n' => RoleEnum::Accountant->value,
        ];

        foreach ($legacyNames as $legacyName => $correctName) {
            $legacyRole = Role::query()->where('name', $legacyName)->where('guard_name', 'web')->first();

            if (! $legacyRole) {
                continue;
            }

            $correctRole = Role::query()->where('name', $correctName)->where('guard_name', 'web')->first();

            if (! $correctRole) {
                $legacyRole->update(['name' => $correctName]);

                continue;
            }

            DB::table('role_has_permissions')
                ->where('role_id', $legacyRole->id)
                ->orderBy('permission_id')
                ->get()
                ->each(function (object $permission) use ($correctRole): void {
                    DB::table('role_has_permissions')->updateOrInsert([
                        'permission_id' => $permission->permission_id,
                        'role_id' => $correctRole->id,
                    ]);
                });

            DB::table('model_has_roles')
                ->where('role_id', $legacyRole->id)
                ->update(['role_id' => $correctRole->id]);

            $legacyRole->delete();
        }
    }
}
