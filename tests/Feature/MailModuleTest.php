<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Livewire\Mail\MailCenterIndex;
use App\Mail\ComposedMail;
use App\Models\Setting;
use App\Models\User;
use App\Services\MailSettingsService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

final class MailModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_mail_center(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin)
            ->get(route('mail.index'))
            ->assertOk()
            ->assertSee('Hộp thư nội bộ');
    }

    public function test_user_without_mail_permission_cannot_access_mail_center(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('mail.index'))
            ->assertForbidden();
    }

    public function test_mail_settings_can_be_saved_with_encrypted_passwords(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        Livewire::test(MailCenterIndex::class)
            ->set('enabled', true)
            ->set('from_name', 'GREECO')
            ->set('from_address', 'no-reply@greeco.vn')
            ->set('imap_host', 'mail.greeco.vn')
            ->set('imap_port', 993)
            ->set('imap_encryption', 'ssl')
            ->set('imap_username', 'no-reply@greeco.vn')
            ->set('imap_password', 'imap-secret')
            ->set('smtp_host', 'mail.greeco.vn')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'no-reply@greeco.vn')
            ->set('smtp_password', 'smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $prefix = 'mail.users.'.$admin->id.'.';

        $this->assertSame('mail.greeco.vn', Setting::get($prefix.'imap_host'));
        $this->assertNotSame('imap-secret', Setting::get($prefix.'imap_password'));
        $this->assertNotSame('smtp-secret', Setting::get($prefix.'smtp_password'));

        $settings = app(MailSettingsService::class)->load();

        $this->assertSame('imap-secret', $settings->imapPassword);
        $this->assertSame('smtp-secret', $settings->smtpPassword);
    }

    public function test_mail_settings_are_scoped_to_the_authenticated_user(): void
    {
        $this->seed(PermissionSeeder::class);

        $alice = User::factory()->create([
            'name' => 'Alice Green',
            'email' => 'alice@greeco.vn',
        ]);
        $alice->givePermissionTo(
            PermissionEnum::MailView->value,
            PermissionEnum::MailUpdate->value,
        );

        $bob = User::factory()->create([
            'name' => 'Bob Green',
            'email' => 'bob@greeco.vn',
        ]);
        $bob->givePermissionTo(
            PermissionEnum::MailView->value,
            PermissionEnum::MailUpdate->value,
        );

        $this->actingAs($alice);

        Livewire::test(MailCenterIndex::class)
            ->set('enabled', false)
            ->set('from_name', 'Alice Green')
            ->set('from_address', 'alice@greeco.vn')
            ->set('imap_host', 'mail.greeco.vn')
            ->set('imap_port', 993)
            ->set('imap_encryption', 'ssl')
            ->set('imap_username', 'alice@greeco.vn')
            ->set('imap_password', 'alice-imap-secret')
            ->set('smtp_host', 'mail.greeco.vn')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'alice@greeco.vn')
            ->set('smtp_password', 'alice-smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();

        Livewire::actingAs($bob)
            ->test(MailCenterIndex::class)
            ->assertSet('from_name', 'Bob Green')
            ->assertSet('from_address', 'bob@greeco.vn')
            ->assertSet('imap_username', '')
            ->assertSet('smtp_username', '');

        Livewire::actingAs($alice)
            ->test(MailCenterIndex::class)
            ->assertSet('from_name', 'Alice Green')
            ->assertSet('from_address', 'alice@greeco.vn')
            ->assertSet('imap_username', 'alice@greeco.vn')
            ->assertSet('smtp_username', 'alice@greeco.vn');
    }

    public function test_default_mail_users_can_configure_their_own_mailbox(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create([
            'name' => 'Staff Mail',
            'email' => 'staff@greeco.vn',
        ]);
        $staff->assignRole(RoleEnum::IT->value);

        $this->actingAs($staff);

        Livewire::test(MailCenterIndex::class)
            ->call('showTab', 'settings')
            ->assertSet('activeTab', 'settings')
            ->set('enabled', false)
            ->set('from_name', 'Staff Mail')
            ->set('from_address', 'staff@greeco.vn')
            ->set('imap_host', 'mail.greeco.vn')
            ->set('imap_port', 993)
            ->set('imap_encryption', 'ssl')
            ->set('imap_username', 'staff@greeco.vn')
            ->set('imap_password', 'staff-imap-secret')
            ->set('smtp_host', 'mail.greeco.vn')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'staff@greeco.vn')
            ->set('smtp_password', 'staff-smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();
    }

    public function test_mail_users_can_configure_their_own_mailbox_without_mail_update_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create([
            'name' => 'Sales Mail',
            'email' => 'sales@greeco.vn',
        ]);
        $user->givePermissionTo(
            PermissionEnum::MailView->value,
            PermissionEnum::MailSend->value,
        );

        $this->actingAs($user);

        Livewire::test(MailCenterIndex::class)
            ->assertSee('Cấu hình')
            ->call('showTab', 'settings')
            ->assertSet('activeTab', 'settings')
            ->assertSee('Cấu hình hòm thư cá nhân')
            ->set('enabled', false)
            ->set('from_name', 'Sales Mail')
            ->set('from_address', 'sales@greeco.vn')
            ->set('imap_host', 'mail.greeco.vn')
            ->set('imap_port', 993)
            ->set('imap_encryption', 'ssl')
            ->set('imap_username', 'sales@greeco.vn')
            ->set('imap_password', 'sales-imap-secret')
            ->set('smtp_host', 'mail.greeco.vn')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'sales@greeco.vn')
            ->set('smtp_password', 'sales-smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();
    }

    public function test_sending_mail_uses_the_authenticated_users_mailbox_login(): void
    {
        Mail::fake();
        $this->seed(PermissionSeeder::class);

        $alice = User::factory()->create([
            'name' => 'Alice Green',
            'email' => 'alice@greeco.vn',
        ]);
        $alice->givePermissionTo(
            PermissionEnum::MailView->value,
            PermissionEnum::MailSend->value,
            PermissionEnum::MailUpdate->value,
        );

        $bob = User::factory()->create([
            'name' => 'Bob Green',
            'email' => 'bob@greeco.vn',
        ]);
        $bob->givePermissionTo(
            PermissionEnum::MailView->value,
            PermissionEnum::MailSend->value,
            PermissionEnum::MailUpdate->value,
        );

        $this->actingAs($alice);
        Livewire::test(MailCenterIndex::class)
            ->set('from_name', 'Alice Green')
            ->set('from_address', 'alice@greeco.vn')
            ->set('imap_username', 'alice@greeco.vn')
            ->set('imap_password', 'alice-imap-secret')
            ->set('smtp_username', 'alice@greeco.vn')
            ->set('smtp_password', 'alice-smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();

        $this->actingAs($bob);
        Livewire::test(MailCenterIndex::class)
            ->set('from_name', 'Bob Green')
            ->set('from_address', 'bob@greeco.vn')
            ->set('imap_username', 'bob@greeco.vn')
            ->set('imap_password', 'bob-imap-secret')
            ->set('smtp_username', 'bob@greeco.vn')
            ->set('smtp_password', 'bob-smtp-secret')
            ->call('saveSettings')
            ->assertHasNoErrors();

        Livewire::test(MailCenterIndex::class)
            ->set('compose_to', 'recipient@example.com')
            ->set('compose_subject', 'Test subject')
            ->set('compose_body', 'Test body')
            ->call('sendMail')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Đã gửi email thành công.');

        $this->assertSame('bob@greeco.vn', config('mail.mailers.smtp.username'));
        $this->assertSame('bob-smtp-secret', config('mail.mailers.smtp.password'));
        Mail::assertSent(ComposedMail::class);
    }

    public function test_user_with_mail_send_permission_can_send_email_from_compose_form(): void
    {
        Mail::fake();
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        Livewire::test(MailCenterIndex::class)
            ->set('from_name', 'GREECO')
            ->set('from_address', 'duan@greeco.vn')
            ->set('smtp_host', 'mail.greeco.vn')
            ->set('smtp_port', 465)
            ->set('smtp_encryption', 'ssl')
            ->set('smtp_username', 'duan@greeco.vn')
            ->set('smtp_password', 'smtp-secret')
            ->set('compose_to', 'recipient@example.com')
            ->set('compose_subject', 'Test subject')
            ->set('compose_body', 'Test body')
            ->call('sendMail')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Đã gửi email thành công.');

        Mail::assertSent(ComposedMail::class);
    }
}
