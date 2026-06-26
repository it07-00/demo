<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\PermissionEnum;
use App\Models\User;
use App\Support\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && $this->user()?->can(PermissionEnum::UserUpdate->value, $user) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $user = $this->route('user');

        return UserValidationRules::update($user instanceof User ? $user : 0);
    }
}
