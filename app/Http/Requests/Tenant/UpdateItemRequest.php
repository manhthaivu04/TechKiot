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
        return true;
    }

    public function rules(): array
    {
        $restaurantId = request()->user()->restaurant_id;

        return [
            'item_group_id' => [
                'sometimes',
                'uuid',
                Rule::exists('item_groups', 'id')->where('restaurant_id', $restaurantId)
            ],
            'name' => 'sometimes|string|max:255',
            'item_type' => ['sometimes', new Enum(ItemType::class)],
            'unit' => 'sometimes|string|max:50',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'description' => 'nullable|string',
            'cost_price' => 'nullable|numeric|min:0',
            'sale_price' => 'sometimes|numeric|min:0',
            'is_active' => 'sometimes|boolean',
            'availability_status' => ['sometimes', new Enum(ItemAvailabilityStatus::class)],
        ];
    }
}
