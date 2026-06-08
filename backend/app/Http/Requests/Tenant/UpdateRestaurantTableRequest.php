<?php

namespace App\Http\Requests\Tenant;

use App\Enums\TableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validate input cho cập nhật bàn ăn.
 *
 * SRP: chỉ validate input.
 * 'sometimes' cho phép PATCH cập nhật từng field.
 */
class UpdateRestaurantTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $restaurantId = $this->user()->restaurant_id;

        return [
            'area_id' => [
                'nullable',
                'uuid',
                Rule::exists('table_areas', 'id')->where('restaurant_id', $restaurantId),
            ],
            'uid'      => 'sometimes|string|max:50',
            'name'     => 'sometimes|required|string|max:100',
            'capacity' => 'sometimes|integer|min:1',
            'status'   => ['sometimes', new Enum(TableStatus::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'area_id.exists' => 'Khu vực bàn không tồn tại hoặc không thuộc nhà hàng của bạn.',
            'capacity.min'   => 'Sức chứa phải từ 1 trở lên.',
        ];
    }
}
