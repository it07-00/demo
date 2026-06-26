<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\DTOs\UserDTO;
use App\Services\UserService;
use App\Support\UserValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
final class UserEdit extends Component
{
    public bool $isOpen = false;

    public ?int $userId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * @var list<string>
     */
    public array $roles = [];

    private UserService $users;

    public function boot(UserService $users): void
    {
        $this->users = $users;
    }

    #[On('user-edit:open')]
    public function open(int $userId): void
    {
        $user = $this->users->find($userId);
        Gate::authorize('update', $user);

        $this->resetValidation();
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->username = $user->username;
        $this->email = $user->email;
        $this->password = '';
        $this->password_confirmation = '';
        $this->roles = $user->roles->pluck('name')->values()->all();
        $this->isOpen = true;
        $this->dispatch('user-edit:show');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->dispatch('user-edit:hide');
    }

    public function save(): void
    {
        abort_if($this->userId === null, 404);

        $user = $this->users->find($this->userId);
        Gate::authorize('update', $user);

        $validated = $this->validate(UserValidationRules::update($user));

        $this->users->update($user, UserDTO::fromArray($validated));

        $this->isOpen = false;
        $this->dispatch('user-edit:hide');
        $this->dispatch('users:refresh');
        session()->flash('status', 'User updated successfully.');
    }

    public function render(): View
    {
        return view('livewire.users.user-edit', [
            'roleOptions' => $this->users->roleOptions(),
        ]);
    }
}
