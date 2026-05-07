<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guarded by route middleware
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
        ];
    }
}
