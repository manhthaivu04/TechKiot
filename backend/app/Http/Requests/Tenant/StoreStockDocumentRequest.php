<?php

namespace App\Http\Requests\Tenant;

use App\Enums\DocumentType;
use App\Enums\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validate input cho tạo chứng từ kho.
 */
class StoreStockDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $restaurantId = $this->user()->restaurant_id;
        $isReceipt = $this->input('document_type') === DocumentType::RECEIPT->value;

        return [
            'warehouse_id' => [
                'required', 'uuid',
                Rule::exists('warehouses', 'id')->where('restaurant_id', $restaurantId),
            ],
            'document_type' => ['required', 'string', new Enum(DocumentType::class)],
            'note'          => 'nullable|string',

            'items'             => 'required|array|min:1',
            'items.*.item_id'   => [
                'required', 'uuid',
                Rule::exists('items', 'id')->where(function ($query) use ($restaurantId) {
                    $query->where('restaurant_id', $restaurantId)
                          ->where('item_type', ItemType::INGREDIENT->value);
                }),
            ],
            'items.*.quantity'  => 'required|numeric|gt:0',
            'items.*.unit_cost' => array_filter([
                $isReceipt ? 'required' : 'nullable',
                'numeric',
                $isReceipt ? 'gt:0' : 'min:0',
            ]),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required'              => 'Chứng từ phải có ít nhất 1 dòng nguyên liệu.',
            'items.min'                   => 'Chứng từ phải có ít nhất 1 dòng nguyên liệu.',
            'items.*.item_id.exists'      => 'Nguyên liệu không tồn tại hoặc không thuộc nhà hàng này.',
            'items.*.quantity.gt'         => 'Số lượng phải lớn hơn 0.',
            'items.*.unit_cost.required'  => 'Phiếu nhập kho bắt buộc nhập đơn giá.',
            'items.*.unit_cost.gt'        => 'Đơn giá nhập kho phải lớn hơn 0.',
            'warehouse_id.exists'         => 'Kho không tồn tại hoặc không thuộc nhà hàng này.',
        ];
    }
}
