<?php

namespace App\Http\Requests\Public;

use App\Enums\MenuSourceType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * FormRequest validate input cho API QR Menu.
 *
 * SRP: validation tập trung tại đây, Controller sạch hoàn toàn.
 * API này public — không cần Sanctum auth, authorize() luôn true.
 */
class GetMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Token nhúng trong QR: có thể là restaurant_id (QR tĩnh) hoặc table_id (QR bàn Pro)
            'public_token' => ['required', 'string', 'uuid'],

            // Loại QR — quyết định Strategy được chọn trong MenuStrategyResolver
            'type'         => ['required', 'string', new Enum(MenuSourceType::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'public_token.required' => 'QR Code không hợp lệ (thiếu token).',
            'public_token.uuid'     => 'QR Code không hợp lệ (token sai định dạng).',
            'type.required'         => 'QR Code không hợp lệ (thiếu loại QR).',
            'type.Illuminate\Validation\Rules\Enum' => 'Loại QR không được hỗ trợ.',
        ];
    }
}
