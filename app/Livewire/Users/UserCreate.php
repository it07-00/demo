<?php

declare(strict_types=1);

namespace App\Livewire\Users;

use App\DTOs\UserDTO;
use App\Models\User;
use App\Services\UserService;
use App\Support\UserValidationRules;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
final class UserCreate extends Component
{
    public bool $isOpen = false;

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

    #[On('user-create:open')]
    public function open(): void
    {
        Gate::authorize('create', User::class);
        $this->resetValidation();
        $this->resetForm();
        $this->isOpen = true;
        $this->dispatch('user-create:show');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->dispatch('user-create:hide');
    }

    public function save(): void
    {
        Gate::authorize('create', User::class);

        $validated = $this->validate(UserValidationRules::store());

        $this->users->create(UserDTO::fromArray($validated));

        $this->isOpen = false;
        $this->dispatch('user-create:hide');
        $this->dispatch('users:refresh');
        session()->flash('status', 'User created successfully.');
    }

    public function render(): View
    {
        return view('livewire.users.user-create', [
            'roleOptions' => $this->users->roleOptions(),
        ]);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->username = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->roles = [];
    }
}
