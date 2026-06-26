<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Profile\ProfileEdit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class ProfileModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_access_profile_edit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_unauthenticated_user_cannot_access_profile_edit(): void
    {
        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_user_can_update_profile_details(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@greeco.com',
            'password' => Hash::make('password'),
            'dob' => null,
            'address' => '',
        ]);

        $this->actingAs($user);

        Livewire::test(ProfileEdit::class)
            ->assertSet('name', 'Old Name')
            ->assertSet('email', 'old@greeco.com')
            ->set('name', 'New Name')
            ->set('email', 'new@greeco.com')
            ->set('dob', '1995-05-15')
            ->set('address', '123 Greeco Street')
            ->set('current_password', 'password')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Cập nhật thông tin cá nhân thành công!');

        $user->refresh();
        $this->assertSame('New Name', $user->name);
        $this->assertSame('new@greeco.com', $user->email);
        $this->assertSame('1995-05-15', $user->dob->format('Y-m-d'));
        $this->assertSame('123 Greeco Street', $user->address);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_user_without_uploaded_avatar_uses_name_initial(): void
    {
        $user = User::factory()->create([
            'name' => 'Nguyen Van An',
            'avatar_path' => null,
        ]);

        $this->assertNull($user->avatar_url);
        $this->assertSame('N', $user->avatar_initials);
    }

    public function test_user_can_upload_avatar(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('avatar.png', 100, 100);

        Livewire::test(ProfileEdit::class)
            ->set('avatarUpload', $file)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Cập nhật thông tin cá nhân thành công!');

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
    }

    public function test_profile_update_validation_rules(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(ProfileEdit::class)
            ->set('name', '')
            ->set('email', 'invalid-email')
            ->set('password', 'short')
            ->call('save')
            ->assertHasErrors(['name', 'email', 'password', 'current_password']);
    }

    public function test_user_must_enter_current_password_to_change_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);

        $this->actingAs($user);

        Livewire::test(ProfileEdit::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'newpassword123')
            ->set('password_confirmation', 'newpassword123')
            ->call('save')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }

    public function test_profile_email_must_be_unique(): void
    {
        $user1 = User::factory()->create(['email' => 'user1@greeco.com']);
        $user2 = User::factory()->create(['email' => 'user2@greeco.com']);

        $this->actingAs($user1);

        Livewire::test(ProfileEdit::class)
            ->set('email', 'user2@greeco.com')
            ->call('save')
            ->assertHasErrors(['email']);
    }
}
