<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DocumentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * Validate input cho tạo chứng từ kho.
 * SRP: chỉ validate, không chứa business logic.
 */
class StoreStockDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization đã xử lý bởi middleware role:OWNER,MANAGER
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'warehouse_id'      => 'required|uuid|exists:warehouses,id',
            'document_type'     => ['required', 'string', new Enum(DocumentType::class)],
            'note'              => 'nullable|string',

            'items'             => 'required|array|min:1',
            'items.*.item_id'   => 'required|uuid|exists:items,id',
            'items.*.quantity'  => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required'           => 'Chứng từ phải có ít nhất 1 dòng nguyên liệu.',
            'items.min'                => 'Chứng từ phải có ít nhất 1 dòng nguyên liệu.',
            'items.*.item_id.exists'   => 'Nguyên liệu không tồn tại.',
            'items.*.quantity.gt'      => 'Số lượng phải lớn hơn 0.',
        ];
    }
}
