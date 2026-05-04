<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code'          => ['sometimes', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/'],
            'name'          => ['sometimes', 'string', 'max:100'],
            'description'   => ['nullable', 'string'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'is_active'     => ['sometimes', 'boolean'],
            'feature_ids'   => ['sometimes', 'array'],
            'feature_ids.*' => ['uuid', 'exists:features,id'],
        ];
    }
}
