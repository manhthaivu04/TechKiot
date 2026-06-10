<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input cho cập nhật khu vực bàn.
 * SRP: chỉ validate, không chứa business logic.
 */
class UpdateTableAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'          => 'sometimes|required|string|max:100',
            'description'   => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
