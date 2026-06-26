<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

final class SettingModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_settings_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_unauthorized_user_cannot_access_settings_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $this->actingAs($director)
            ->get(route('settings.index'))
            ->assertStatus(403);
    }

    public function test_settings_can_be_retrieved_and_saved(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        // Pre-set some value
        Setting::set('website_name', 'Greeco Old');

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Settings\SettingIndex::class)
            ->assertSet('website_name', 'Greeco Old')
            ->set('website_name', 'Greeco New')
            ->set('contact_email', 'newadmin@greeco.com')
            ->set('timezone', 'Asia/Ho_Chi_Minh')
            ->set('language', 'vi')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Lưu cấu hình hệ thống thành công!');

        $this->assertSame('Greeco New', Setting::get('website_name'));
        $this->assertSame('newadmin@greeco.com', Setting::get('contact_email'));
    }

    public function test_clear_cache_action(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Settings\SettingIndex::class)
            ->call('clearCache')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Xóa toàn bộ cache hệ thống (Cache, View, Route, Config) thành công!');
    }
}
