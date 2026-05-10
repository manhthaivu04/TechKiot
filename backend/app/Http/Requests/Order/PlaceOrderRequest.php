<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderSourceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation cho API đặt đơn hàng mới.
 *
 * Tách validation ra FormRequest để:
 * 1. Controller gọn hơn — chỉ điều phối, không validate.
 * 2. Validation rules được document rõ ràng tại một nơi.
 * 3. Reusable: nhiều Controller có thể dùng chung request này.
 */
class PlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization kiểm tra feature middleware trong route — không làm ở đây
        return true;
    }

    public function rules(): array
    {
        return [
            'source_channel' => ['required', Rule::enum(OrderSourceChannel::class)],

            // items phải là mảng, có ít nhất 1 món
            'items'              => ['required', 'array', 'min:1'],
            'items.*.item_id'    => ['required', 'uuid'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.note'       => ['nullable', 'string', 'max:255'],

            // Optional — Pro fields (nhận vào nhưng validate nhẹ)
            'table_id'           => ['nullable', 'uuid'],
            'guest_count'        => ['nullable', 'integer', 'min:1', 'max:999'],
            'customer_name'      => ['nullable', 'string', 'max:100'],
            'customer_phone'     => ['nullable', 'string', 'max:20'],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'note'               => ['nullable', 'string', 'max:1000'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'tax_rate'           => ['nullable', 'numeric', 'min:0', 'max:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'source_channel.required' => 'Vui lòng chỉ định kênh tạo đơn.',
            'items.required'          => 'Đơn hàng phải có ít nhất một món.',
            'items.*.item_id.uuid'    => 'ID món hàng không hợp lệ.',
            'items.*.quantity.min'    => 'Số lượng tối thiểu là 1.',
        ];
    }
}
