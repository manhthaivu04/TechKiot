<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = (string) $this->route('staff');

        return [
            'name'      => ['sometimes', 'string', 'max:150'],
            'email'     => ['sometimes', 'string', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)],
            'password'  => ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            'role'      => [
                'sometimes',
                'string',
                Rule::in([
                    UserRole::MANAGER->value,
                    UserRole::WAITER->value,
                    UserRole::KITCHEN->value,
                    UserRole::CASHIER->value,
                ]),
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

