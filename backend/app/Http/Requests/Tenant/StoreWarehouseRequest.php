<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input cho tạo kho chứa.
 * SRP: chỉ validate, không chứa business logic.
 */
class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization đã xử lý bởi middleware role:OWNER,MANAGER
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'       => 'required|string|max:100',
            'is_default' => 'sometimes|boolean',
        ];
    }
}
