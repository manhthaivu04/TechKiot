<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OnboardRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guarded by route middleware
    }

    public function rules(): array
    {
        return [
            // restaurant
            'restaurant.name'    => ['required', 'string', 'max:150'],
            'restaurant.address' => ['nullable', 'string', 'max:255'],
            'restaurant.phone'   => ['nullable', 'string', 'max:20'],

            // subscription
            'package_id' => ['required', 'uuid', 'exists:packages,id'],

            // owner user
            'owner.name'      => ['required', 'string', 'max:150'],
            'owner.email'     => ['required', 'string', 'email', 'max:150'],
            'owner.password'  => ['required', 'string', 'min:8'],
            'owner.is_active' => ['sometimes', 'boolean'],
        ];
    }
}

