<?php

declare(strict_types=1);

namespace App\Services;

use App\Actions\Users\EnsureUserCanBeDeleted;
use App\DTOs\UserDTO;
use App\DTOs\UserFilterDTO;
use App\Enums\RoleEnum;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

final class UserService
{
    public const DEFAULT_RESET_PASSWORD = '1Zbia9HdgUizySSt';

    public function __construct(
        private readonly UserRepository $users,
        private readonly EnsureUserCanBeDeleted $ensureUserCanBeDeleted,
    ) {}

    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilterDTO $filter): LengthAwarePaginator
    {
        return $this->users->paginate($filter);
    }

    public function find(int $id): User
    {
        return $this->users->find($id);
    }

    public function create(UserDTO $dto): User
    {
        return DB::transaction(function () use ($dto): User {
            $user = $this->users->create([
                'name' => $dto->name,
                'username' => $dto->username,
                'email' => $dto->email,
                'password' => Hash::make((string) $dto->password),
            ]);

            $this->assignRole($user, $dto->roles);

            return $user->refresh()->load('roles:id,name');
        });
    }

    public function update(User $user, UserDTO $dto): User
    {
        return DB::transaction(function () use ($user, $dto): User {
            $attributes = [
                'name' => $dto->name,
                'username' => $dto->username,
                'email' => $dto->email,
            ];

            if ($dto->password !== null) {
                $attributes['password'] = Hash::make($dto->password);
            }

            $user = $this->users->update($user, $attributes);
            $this->assignRole($user, $dto->roles);

            return $user->refresh()->load('roles:id,name');
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->ensureUserCanBeDeleted->handle($user);
            $this->users->delete($user);
        });
    }

    public function lock(User $user, ?User $actor = null): User
    {
        return DB::transaction(function () use ($user, $actor): User {
            $this->ensureUserCanBeLocked($user, $actor);

            return $this->users->update($user, [
                'locked_at' => now(),
            ]);
        });
    }

    public function unlock(User $user): User
    {
        return DB::transaction(function () use ($user): User {
            return $this->users->update($user, [
                'locked_at' => null,
            ]);
        });
    }

    public function resetPassword(User $user, string $password): User
    {
        return DB::transaction(function () use ($user, $password): User {
            return $this->users->update($user, [
                'password' => Hash::make($password),
            ]);
        });
    }

    public function resetPasswordToDefault(User $user): User
    {
        return $this->resetPassword($user, self::DEFAULT_RESET_PASSWORD);
    }

    /**
     * @param  list<string>  $roles
     */
    public function assignRole(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    /**
     * @return Collection<int, Role>
     */
    public function roleOptions(): Collection
    {
        return Role::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();
    }

    private function ensureUserCanBeLocked(User $user, ?User $actor): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Không thể khóa chính tài khoản đang đăng nhập.',
            ]);
        }

        if (! $user->hasRole(RoleEnum::SuperAdmin->value)) {
            return;
        }

        $superAdminRole = Role::query()
            ->where('name', RoleEnum::SuperAdmin->value)
            ->first();

        if ($superAdminRole !== null && $superAdminRole->users()
            ->whereNull('users.locked_at')
            ->where('users.id', '!=', $user->id)
            ->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => 'Không thể khóa tài khoản Super Admin hoạt động cuối cùng.',
        ]);
    }
}
