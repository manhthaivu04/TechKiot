<?php

namespace App\Http\Requests\Tenant;

use App\Enums\ItemAvailabilityStatus;
use App\Enums\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Đổi item_type sang INGREDIENT yêu cầu gói có tính năng INVENTORY_MANAGEMENT.
        // Ngăn không cho gói Basic "lén" chuyển món ăn thành nguyên liệu.
        if ($this->input('item_type') === ItemType::INGREDIENT->value) {
            return $this->user()->restaurant->hasFeature('INVENTORY_MANAGEMENT');
        }

        return true;
    }

    public function rules(): array
    {
        $restaurantId = request()->user()->restaurant_id;

        return [
            'item_group_id' => [
                'sometimes',
                'nullable',
                'uuid',
                Rule::exists('item_groups', 'id')->where('restaurant_id', $restaurantId)
            ],
            'name' => 'sometimes|string|max:255',
            'item_type' => ['sometimes', new Enum(ItemType::class)],
            'unit' => 'sometimes|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'sometimes|nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'availability_status' => ['sometimes', new Enum(ItemAvailabilityStatus::class)],
        ];
    }
}
