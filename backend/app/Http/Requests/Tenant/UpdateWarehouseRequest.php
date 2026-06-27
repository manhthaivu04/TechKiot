<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input cho cập nhật kho chứa.
 * SRP: chỉ validate, không chứa business logic.
 */
class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'       => 'sometimes|required|string|max:100',
            'is_default' => 'sometimes|boolean',
        ];
    }
}
