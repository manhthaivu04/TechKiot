<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'            => ['required', 'array', 'min:1'],
            'items.*.item_id'  => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.note'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'         => 'Phải cung cấp ít nhất một món.',
            'items.*.item_id.uuid'   => 'ID món hàng không hợp lệ.',
            'items.*.quantity.min'   => 'Số lượng tối thiểu là 1.',
        ];
    }
}
