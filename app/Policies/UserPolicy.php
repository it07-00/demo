<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;

final class UserPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole(RoleEnum::SuperAdmin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UserView->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UserView->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UserCreate->value);
    }

    public function update(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UserUpdate->value);
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UserDelete->value);
    }
}
