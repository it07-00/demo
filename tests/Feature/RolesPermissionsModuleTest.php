<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Livewire\RolesPermissions\RolesPermissionsIndex;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class RolesPermissionsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_roles_permissions(): void
    {
        $this->get(route('roles-permissions.index'))
            ->assertRedirect(route('login'));
    }

    public function test_unauthorized_user_cannot_access_roles_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::Director->value); // Director does not have role.manage

        $this->actingAs($staff)
            ->get(route('roles-permissions.index'))
            ->assertStatus(403);
    }

    public function test_authorized_user_can_access_roles_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value); // Admin has role.manage

        $this->actingAs($admin)
            ->get(route('roles-permissions.index'))
            ->assertOk();
    }

    public function test_component_mounts_with_first_role_as_active(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $firstRole = Role::query()->orderBy('id')->first();

        Livewire::test(RolesPermissionsIndex::class)
            ->assertSet('activeRoleId', $firstRole->id);
    }

    public function test_can_select_different_role(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $roles = Role::query()->orderBy('id')->get();
        $secondRole = $roles[1];

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $secondRole->id)
            ->assertSet('activeRoleId', $secondRole->id);
    }

    public function test_can_create_new_role_with_validation(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        // Test validation empty name
        Livewire::test(RolesPermissionsIndex::class)
            ->set('newRoleName', '')
            ->call('createRole')
            ->assertHasErrors(['newRoleName' => 'required']);

        // Test creation success
        Livewire::test(RolesPermissionsIndex::class)
            ->set('newRoleName', 'Custom Editor')
            ->set('newRoleDescription', 'Custom role description')
            ->call('createRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'name' => 'Custom Editor',
            'description' => 'Custom role description',
        ]);
    }

    public function test_can_toggle_permission_on_role(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        // Find standard non-SuperAdmin role
        $itRole = Role::findByName(RoleEnum::IT->value);

        // Mount and toggle ScheduleCreate permission
        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $itRole->id)
            ->call('togglePermission', PermissionEnum::ScheduleCreate->value)
            ->assertHasNoErrors();

        // Since it starts assigned in the seeder, toggling it should remove it
        $this->assertFalse($itRole->refresh()->hasPermissionTo(PermissionEnum::ScheduleCreate->value));

        // Toggle again to add it back
        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $itRole->id)
            ->call('togglePermission', PermissionEnum::ScheduleCreate->value)
            ->assertHasNoErrors();

        $this->assertTrue($itRole->refresh()->hasPermissionTo(PermissionEnum::ScheduleCreate->value));
    }

    public function test_permission_toggles_keep_required_view_permissions_consistent(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $role = Role::create([
            'name' => 'Project Coordinator',
            'guard_name' => 'web',
        ]);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::ScheduleCreate->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::ScheduleView->value));
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::ScheduleCreate->value));

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::ScheduleView->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::ScheduleView->value));
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::ScheduleCreate->value));
    }

    public function test_module_permissions_keep_dashboard_access_consistent(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $role = Role::create([
            'name' => 'Mail Operator',
            'guard_name' => 'web',
        ]);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::MailSend->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::DashboardView->value));
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::MailView->value));
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::MailSend->value));

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::DashboardView->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::DashboardView->value));
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::MailView->value));
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::MailSend->value));
    }

    public function test_operation_permissions_are_visible_in_admin(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        Livewire::test(RolesPermissionsIndex::class)
            ->assertSee('Quản trị vận hành')
            ->assertSee('Xem Dự án &amp; Khách hàng', false)
            ->assertSee('Xem KPI &amp; Hiệu suất', false)
            ->assertSee('Xem CRM khách hàng')
            ->assertSee('Xem Cảnh báo vận hành');
    }

    public function test_operation_permissions_keep_dashboard_access_consistent(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $role = Role::create([
            'name' => 'Operations Viewer',
            'guard_name' => 'web',
        ]);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::AnalyticsView->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::DashboardView->value));
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::AnalyticsView->value));

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $role->id)
            ->call('togglePermission', PermissionEnum::DashboardView->value)
            ->assertHasNoErrors();

        $role->refresh();
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::DashboardView->value));
        $this->assertFalse($role->hasPermissionTo(PermissionEnum::AnalyticsView->value));
    }

    public function test_cannot_toggle_super_admin_permissions(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $superAdminRole = Role::findByName(RoleEnum::SuperAdmin->value);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('selectRole', $superAdminRole->id)
            ->call('togglePermission', PermissionEnum::UserCreate->value)
            ->assertHasNoErrors();

        // Super Admin still retains the permission
        $this->assertTrue($superAdminRole->refresh()->hasPermissionTo(PermissionEnum::UserCreate->value));
    }

    public function test_can_delete_custom_role_but_cannot_delete_super_admin(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        // Create custom role first
        $customRole = Role::create([
            'name' => 'Temporary Role',
            'guard_name' => 'web',
        ]);

        // Delete custom role
        Livewire::test(RolesPermissionsIndex::class)
            ->call('deleteRole', $customRole->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('roles', ['id' => $customRole->id]);

        // Attempt delete Super Admin
        $superAdminRole = Role::findByName(RoleEnum::SuperAdmin->value);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('deleteRole', $superAdminRole->id)
            ->assertHasNoErrors();

        // Super Admin role must still exist
        $this->assertDatabaseHas('roles', ['id' => $superAdminRole->id]);
    }

    public function test_builtin_roles_cannot_be_renamed_or_deleted(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        $directorRole = Role::findByName(RoleEnum::Director->value);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('openEditModal', $directorRole->id)
            ->set('editRoleName', 'Executive')
            ->call('updateRole')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $directorRole->id,
            'name' => RoleEnum::Director->value,
        ]);

        Livewire::test(RolesPermissionsIndex::class)
            ->call('deleteRole', $directorRole->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('roles', [
            'id' => $directorRole->id,
            'name' => RoleEnum::Director->value,
        ]);
    }
}
