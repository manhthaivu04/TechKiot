<?php

namespace App\Http\Requests\Tenant;

use App\Enums\TableStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Validate input cho tạo bàn ăn.
 *
 * SRP: chỉ validate input.
 * area_id phải thuộc cùng restaurant_id — đã validate ở đây bằng Rule::exists.
 * uid nếu truyền phải unique trong cùng nhà hàng (business logic validate thêm ở Service).
 */
class StoreRestaurantTableRequest extends FormRequest
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
            'uid'      => 'nullable|string|max:50',
            'name'     => 'required|string|max:100',
            'capacity' => 'nullable|integer|min:1',
            'status'   => ['nullable', new Enum(TableStatus::class)],
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
