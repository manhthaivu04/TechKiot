<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_]+$/'],
            'name'        => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Feature code must be UPPER_SNAKE_CASE (e.g. TABLE_MANAGEMENT).',
        ];
    }
}
