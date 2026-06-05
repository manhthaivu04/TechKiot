<?php

namespace App\Http\Requests\Tenant;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'      => ['required', 'string', 'max:150'],
            'email'     => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8', 'max:255'],
            'role'      => [
                'required',
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

