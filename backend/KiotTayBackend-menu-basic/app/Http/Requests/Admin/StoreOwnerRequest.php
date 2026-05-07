<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guarded by route middleware
    }

    public function rules(): array
    {
        return [
            'restaurant_id' => ['required', 'uuid', 'exists:restaurants,id'],
            'name'          => ['required', 'string', 'max:150'],
            'email'         => ['required', 'string', 'email', 'max:150'],
            'password'      => ['required', 'string', 'min:8'],
            'is_active'     => ['sometimes', 'boolean'],
        ];
    }
}

