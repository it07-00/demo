<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class EnsureUserCanBeDeleted
{
    /**
     * @throws ValidationException
     */
    public function handle(User $user): void
    {
        if (! $user->hasRole(RoleEnum::SuperAdmin->value)) {
            return;
        }

        $superAdminRole = Role::query()
            ->where('name', RoleEnum::SuperAdmin->value)
            ->first();

        if ($user->isLocked()) {
            return;
        }

        if ($superAdminRole !== null && $superAdminRole->users()
            ->whereNull('users.locked_at')
            ->where('users.id', '!=', $user->id)
            ->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => 'Không thể xóa tài khoản Super Admin hoạt động cuối cùng.',
        ]);
    }
}
