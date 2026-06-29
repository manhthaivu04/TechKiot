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
            'feature_ids'             => ['sometimes', 'array'],
            'feature_ids.*'           => ['uuid', 'exists:features,id'],
            'prices'                  => ['sometimes', 'array'],
            'prices.*.id'             => ['sometimes', 'nullable', 'uuid'],
            'prices.*.duration_days'  => ['required_with:prices', 'integer', 'min:1'],
            'prices.*.price'          => ['required_with:prices', 'numeric', 'min:0'],
            'prices.*.original_price' => ['nullable', 'numeric', 'min:0'],
            'prices.*.is_active'      => ['sometimes', 'boolean'],
        ];
    }
}
