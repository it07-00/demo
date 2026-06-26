<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\UserFilterDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserRepository
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(UserFilterDTO $filter): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($filter->search, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($filter->role, fn ($query, string $role) => $query->role($role))
            ->orderBy($filter->sortField, $filter->sortDirection)
            ->paginate($filter->perPage);
    }

    public function find(int $id): User
    {
        return User::query()
            ->with('roles:id,name')
            ->findOrFail($id);
    }

    /**
     * @param  array{name: string, username: string, email: string, password: string}  $attributes
     */
    public function create(array $attributes): User
    {
        return User::query()->create($attributes);
    }

    /**
     * @param  array{name?: string, username?: string, email?: string, password?: string, locked_at?: mixed}  $attributes
     */
    public function update(User $user, array $attributes): User
    {
        $user->update($attributes);

        return $user->refresh()->load('roles:id,name');
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
