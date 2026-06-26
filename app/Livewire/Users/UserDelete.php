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
final class UserDelete extends Component
{
    public bool $isOpen = false;

    public ?int $userId = null;

    public string $name = '';

    private UserService $users;

    public function boot(UserService $users): void
    {
        $this->users = $users;
    }

    #[On('user-delete:open')]
    public function open(int $userId): void
    {
        $user = $this->users->find($userId);
        Gate::authorize('delete', $user);

        $this->resetErrorBag();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->isOpen = true;
        $this->dispatch('user-delete:show');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetErrorBag();
        $this->dispatch('user-delete:hide');
    }

    public function delete(): void
    {
        abort_if($this->userId === null, 404);

        $user = $this->users->find($this->userId);
        Gate::authorize('delete', $user);

        $this->users->delete($user);

        $this->isOpen = false;
        $this->dispatch('user-delete:hide');
        $this->dispatch('users:refresh');
        session()->flash('status', 'User deleted successfully.');
    }

    public function render(): View
    {
        return view('livewire.users.user-delete');
    }
}
