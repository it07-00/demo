<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\PermissionEnum;
use App\Support\UserValidationRules;
use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::UserCreate->value) === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return UserValidationRules::store();
    }
}
