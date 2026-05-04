<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/'],
            'name'          => ['required', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'price'         => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'is_active'     => ['sometimes', 'boolean'],
            'feature_ids'   => ['sometimes', 'array'],
            'feature_ids.*' => ['uuid', 'exists:features,id'],
        ];
    }
}
