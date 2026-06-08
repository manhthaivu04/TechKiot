<?php

namespace App\Http\Requests\Tenant;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate input cho tạo khu vực bàn.
 * SRP: chỉ validate, không chứa business logic.
 */
class StoreTableAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization đã xử lý bởi middleware role:OWNER
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:100',
            'description'   => 'nullable|string',
            'display_order' => 'nullable|integer|min:0',
        ];
    }
}
