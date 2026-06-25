<?php

namespace App\Http\Requests\Tenant;

use App\Enums\ItemAvailabilityStatus;
use App\Enums\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tạo nguyên liệu (INGREDIENT) yêu cầu gói có tính năng INVENTORY_MANAGEMENT.
        // Nếu nhà hàng chưa mua gói Premium, trả về 403 Forbidden ngay tại đây.
        if ($this->input('item_type') === ItemType::INGREDIENT->value) {
            return $this->user()->restaurant->hasFeature('INVENTORY_MANAGEMENT');
        }

        return true;
    }

    public function rules(): array
    {
        $restaurantId = request()->user()->restaurant_id; // Giả sử auth user chứa restaurant_id

        return [
            'item_group_id' => [
                Rule::requiredIf(fn () => $this->input('item_type') !== ItemType::INGREDIENT->value),
                'nullable',
                'uuid',
                Rule::exists('item_groups', 'id')->where('restaurant_id', $restaurantId)
            ],
            'name' => 'required|string|max:255',
            'item_type' => ['required', new Enum(ItemType::class)],
            'unit' => 'required|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => [
                Rule::requiredIf(fn () => $this->input('item_type') !== ItemType::INGREDIENT->value),
                'nullable',
                'numeric',
                'min:0'
            ],
            'is_active' => 'sometimes|boolean',
            'availability_status' => ['required', new Enum(ItemAvailabilityStatus::class)],
        ];
    }
}
