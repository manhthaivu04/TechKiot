<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['sometimes', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
        ];
    }
}
