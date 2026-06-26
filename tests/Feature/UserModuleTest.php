<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\UserDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Livewire\Users\UserIndex;
use App\Livewire\Users\UserResetPassword;
use App\Models\User;
use App\Services\UserService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class UserModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_seeder_creates_default_roles_permissions_and_super_admin(): void
    {
        $this->seed(PermissionSeeder::class);

        foreach (PermissionEnum::cases() as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission->value,
                'guard_name' => 'web',
            ]);
        }

        foreach (RoleEnum::cases() as $role) {
            $this->assertDatabaseHas('roles', [
                'name' => $role->value,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = User::query()->where('username', 'superadmin')->firstOrFail();

        $this->assertTrue($superAdmin->hasRole(RoleEnum::SuperAdmin->value));
    }

    public function test_users_login_with_username_not_email(): void
    {
        $user = User::factory()->create([
            'username' => 'staff',
            'email' => 'staff@example.com',
            'password' => Hash::make('password'),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'staff',
                'password' => 'password',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        Auth::guard('web')->logout();

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => 'staff@example.com',
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username']);

        $this->assertGuest();
    }

    public function test_authorized_user_can_access_user_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->givePermissionTo(PermissionEnum::DashboardView->value, PermissionEnum::UserView->value);

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_user_service_creates_updates_and_soft_deletes_user(): void
    {
        $this->seed(PermissionSeeder::class);

        $service = app(UserService::class);

        $user = $service->create(UserDTO::fromArray([
            'name' => 'Staff Member',
            'username' => 'staff',
            'email' => 'staff@example.com',
            'password' => 'password',
            'roles' => [RoleEnum::Director->value],
        ]));

        $this->assertTrue(Hash::check('password', $user->password));
        $this->assertTrue($user->hasRole(RoleEnum::Director->value));

        $updated = $service->update($user, UserDTO::fromArray([
            'name' => 'Admin Member',
            'username' => 'admin.member',
            'email' => 'admin.member@example.com',
            'roles' => [RoleEnum::IT->value],
        ]));

        $this->assertSame('Admin Member', $updated->name);
        $this->assertSame('admin.member', $updated->username);
        $this->assertSame('admin.member@example.com', $updated->email);
        $this->assertTrue($updated->hasRole(RoleEnum::IT->value));

        $service->resetPasswordToDefault($updated);
        $this->assertTrue(Hash::check(UserService::DEFAULT_RESET_PASSWORD, $updated->refresh()->password));

        $service->delete($updated);

        $this->assertSoftDeleted('users', [
            'id' => $updated->id,
        ]);
    }

    public function test_user_service_locks_and_unlocks_user(): void
    {
        $this->seed(PermissionSeeder::class);

        $service = app(UserService::class);
        $user = User::factory()->create();

        $locked = $service->lock($user);

        $this->assertNotNull($locked->locked_at);
        $this->assertTrue($locked->isLocked());

        $unlocked = $service->unlock($locked);

        $this->assertNull($unlocked->locked_at);
        $this->assertFalse($unlocked->isLocked());
    }

    public function test_locked_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'username' => 'locked',
            'email' => 'locked@example.com',
            'password' => Hash::make('password'),
            'locked_at' => now(),
        ]);

        $this->from(route('login'))
            ->post(route('login.store'), [
                'username' => $user->username,
                'password' => 'password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username']);

        $this->assertGuest();
    }

    public function test_locked_authenticated_user_is_logged_out_on_next_request(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create([
            'locked_at' => now(),
        ]);
        $user->givePermissionTo(PermissionEnum::DashboardView->value);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username']);

        $this->assertGuest();
    }

    public function test_user_index_can_lock_unlock_and_open_reset_password_modal(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->givePermissionTo(
            PermissionEnum::UserView->value,
            PermissionEnum::UserUpdate->value,
        );

        $user = User::factory()->create();

        $this->actingAs($admin);

        Livewire::test(UserIndex::class)
            ->call('lock', $user->id)
            ->assertHasNoErrors();

        $this->assertTrue($user->refresh()->isLocked());

        Livewire::test(UserIndex::class)
            ->call('unlock', $user->id)
            ->assertHasNoErrors();

        $this->assertFalse($user->refresh()->isLocked());

        Livewire::test(UserResetPassword::class)
            ->call('open', $user->id)
            ->assertSet('defaultPassword', UserService::DEFAULT_RESET_PASSWORD)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check(UserService::DEFAULT_RESET_PASSWORD, $user->refresh()->password));
    }

    public function test_director_role_does_not_have_user_delete_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $permission = Permission::query()
            ->where('name', PermissionEnum::UserDelete->value)
            ->firstOrFail();

        $role = Role::findByName(RoleEnum::Director->value);

        $this->assertFalse($role->hasPermissionTo($permission));
    }

    public function test_default_non_admin_roles_cannot_access_user_management(): void
    {
        $this->seed(PermissionSeeder::class);

        foreach ([RoleEnum::Director, RoleEnum::IT, RoleEnum::Sales, RoleEnum::Consultant, RoleEnum::Accountant] as $role) {
            $user = User::factory()->create();
            $user->assignRole($role->value);

            $this->actingAs($user)
                ->get(route('users.index'))
                ->assertStatus(403);
        }
    }
}
