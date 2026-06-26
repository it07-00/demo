<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\Services\UserService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
final class UserResetPassword extends Component
{
    public bool $isOpen = false;

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $defaultPassword = UserService::DEFAULT_RESET_PASSWORD;

    private UserService $users;

    public function boot(UserService $users): void
    {
        $this->users = $users;
    }

    #[On('user-reset-password:open')]
    public function open(int $userId): void
    {
        $user = $this->users->find($userId);
        Gate::authorize('update', $user);

        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->isOpen = true;
        $this->dispatch('user-reset-password:show');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->dispatch('user-reset-password:hide');
    }

    public function save(): void
    {
        abort_if($this->userId === null, 404);

        $user = $this->users->find($this->userId);
        Gate::authorize('update', $user);

        $this->users->resetPasswordToDefault($user);

        $this->isOpen = false;
        $this->dispatch('user-reset-password:hide');
        $this->dispatch('users:refresh');
        session()->flash('status', 'Đã đặt lại mật khẩu người dùng về mật khẩu mặc định.');
    }

    public function render(): View
    {
        return view('livewire.users.user-reset-password');
    }
}
