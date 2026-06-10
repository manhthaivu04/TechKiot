<?php

namespace App\Http\Requests\Public;

use App\Enums\OrderSourceChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * PublicPlaceOrderRequest — Validate đơn đặt món từ khách hàng quét QR.
 *
 * HIỆN CHỈ HỖ TRỢ `qr_static` (QR tĩnh nhà hàng).
 * `qr_table` (QR theo bàn — gói Pro) CHƯA được hỗ trợ ở public order vì:
 *   - qr_token của bàn sinh dạng `tbl_{uuid}` (không phải UUID thuần).
 *   - Luồng QR bàn (gán order vào bàn, update trạng thái bàn) chưa hoàn thiện.
 * Khi module QR bàn sẵn sàng, thêm lại QrTable vào whitelist và mở rộng
 * validation `public_token` cho phù hợp.
 *
 * ── Khác biệt với PlaceOrderRequest (Tenant) ────────────────────────────────
 * PlaceOrderRequest (Tenant):
 *   - source_channel cho phép: cashier, qr_static, qr_table
 *   - restaurant_id lấy từ Auth::user()->restaurant_id
 *   - Không cần public_token
 *
 * PublicPlaceOrderRequest (Public / Guest):
 *   - source_channel chỉ cho phép: qr_static (xem lý do ở trên)
 *   - restaurant_id được RESOLVE từ public_token trong Controller (không phải Auth)
 *   - Bắt buộc có public_token (UUID restaurant_id) để Controller biết nhà hàng nào
 *   - Không cần Sanctum token — authorize() luôn true
 *
 * ── Design Pattern áp dụng ──────────────────────────────────────────────────
 * Whitelist Enum Validation: Dùng Rule::in() thay vì Rule::enum() toàn bộ
 * để chỉ cho phép một tập con của OrderSourceChannel,
 * bảo vệ hệ thống khỏi việc khách giả mạo kênh nội bộ (cashier).
 */
class PublicPlaceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        // API public — không cần đăng nhập
        return true;
    }

    public function rules(): array
    {
        return [
            // Token nhúng trong QR code — Controller dùng để resolve restaurant_id
            'public_token' => ['required', 'string', 'uuid'],

            // Chỉ cho phép QR tĩnh — QR bàn (qr_table) chưa hỗ trợ, cashier là kênh nội bộ
            'source_channel' => [
                'required',
                Rule::in([
                    OrderSourceChannel::QrStatic->value,
                ]),
            ],

            // Danh sách món đặt
            'items'              => ['required', 'array', 'min:1'],
            'items.*.item_id'    => ['required', 'uuid'],
            'items.*.quantity'   => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.note'       => ['nullable', 'string', 'max:255'],

            // Thông tin khách (optional — giúp nhân viên nhận diện đơn)
            'customer_name'      => ['nullable', 'string', 'max:100'],
            'customer_phone'     => ['nullable', 'string', 'max:20'],
            'customer_reference' => ['nullable', 'string', 'max:100'],
            'guest_count'        => ['nullable', 'integer', 'min:1', 'max:50'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'public_token.required'     => 'QR Code không hợp lệ (thiếu token).',
            'public_token.uuid'         => 'QR Code không hợp lệ (token sai định dạng).',
            'source_channel.required'   => 'Vui lòng chỉ định kênh đặt hàng.',
            'source_channel.in'         => 'Hiện chỉ hỗ trợ đặt món qua QR tĩnh của nhà hàng (qr_static).',
            'items.required'            => 'Vui lòng chọn ít nhất một món.',
            'items.*.item_id.uuid'      => 'ID món hàng không hợp lệ.',
            'items.*.quantity.min'      => 'Số lượng tối thiểu là 1.',
        ];
    }
}
