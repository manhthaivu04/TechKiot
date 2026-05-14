<?php

namespace App\Http\Controllers\Api\Public;

use App\Contracts\Services\OrderServiceInterface;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderSourceChannel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Public\PublicPlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;

/**
 * PublicOrderController — Cho phép khách hàng tự đặt món qua mã QR.
 *
 * ── Design Patterns áp dụng ──────────────────────────────────────────────────
 *
 * 1. Facade Pattern (OrderService):
 *    Controller này inject OrderServiceInterface — cùng interface mà
 *    Tenant\OrderController (nhân viên) đang dùng.
 *    Nghĩa là một Service duy nhất phục vụ cả 2 loại người dùng,
 *    không cần nhân đôi business logic.
 *
 * 2. Strategy Pattern (Chạy ngầm bên dưới):
 *    PlaceOrderDTO chứa source_channel = qr_static hoặc qr_table.
 *    PlaceOrderAction sẽ dựa vào đây để kích hoạt đúng Strategy:
 *    → QrStaticOrderStrategy: bắn thông báo real-time cho nhân viên.
 *    → QrTableOrderStrategy (Pro): gán order vào bàn, update status bàn.
 *    Controller không biết Strategy nào đang chạy — hoàn toàn minh bạch.
 *
 * 3. DTO Pattern (PlaceOrderDTO):
 *    Bảo vệ tầng Service khỏi raw HTTP data. Controller chịu trách nhiệm
 *    xây dựng DTO đúng cách (resolve restaurant_id từ token, set created_by).
 *    Service chỉ làm việc với dữ liệu đã được type-safe và sạch.
 *
 * ── Điểm khác biệt so với Tenant\OrderController ────────────────────────────
 * Tenant\OrderController:
 *   - restaurant_id = Auth::user()->restaurant_id (từ JWT/Sanctum token)
 *   - created_by    = Auth::user()->id
 *   - source_channel: cashier | qr_static | qr_table (mọi kênh)
 *
 * PublicOrderController (file này):
 *   - restaurant_id được RESOLVE từ public_token trong QR (không phải Auth)
 *   - created_by    = 'system_qr' (placeholder — không có user đăng nhập)
 *   - source_channel: chỉ qr_static | qr_table (FormRequest đã chặn cashier)
 *
 * SRP: Controller chỉ làm 4 việc: Nhận Request → Resolve context → Build DTO → Return JSON.
 */
class PublicOrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    /**
     * POST /api/public/orders
     *
     * Body:
     *   - public_token:   UUID (restaurant_id cho qr_static | table_id cho qr_table)
     *   - source_channel: string (qr_static | qr_table)
     *   - items:          array[{item_id, quantity, note?}]
     *   - customer_name?  string
     *   - customer_phone? string
     *   - note?           string
     */
    public function store(PublicPlaceOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // ── Bước quan trọng: Resolve restaurant_id từ public_token ───────────
        // Khác với nhân viên (lấy từ Auth token), khách hàng không đăng nhập.
        // Chúng ta phải dùng public_token trong QR Code để xác định nhà hàng.
        //
        // Logic resolve phụ thuộc vào source_channel:
        //   QrStatic: public_token = restaurant_id (trực tiếp)
        //   QrTable:  public_token = table_id → cần query bảng tables để ra restaurant_id
        $restaurantId = $this->resolveRestaurantId(
            publicToken: $validated['public_token'],
            channel:     OrderSourceChannel::from($validated['source_channel']),
        );

        // ── Xây dựng DTO — cùng class với Tenant, chỉ khác context ──────────
        $dto = PlaceOrderDTO::fromArray(
            restaurantId: $restaurantId,
            createdBy:    'system_qr', // Không có user đăng nhập → đánh dấu đơn tự phục vụ
            data:         $validated,
        );

        // ── Gọi Service — TÁI SỬ DỤNG HOÀN TOÀN, không viết lại logic tính tiền
        $order = $this->orderService->placeOrder($dto);

        return response()->json([
            'message' => 'Đặt món thành công! Đơn hàng của bạn đang được chuẩn bị.',
            'data'    => new OrderResource($order),
        ], 201);
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * Resolve restaurant_id từ public_token tùy theo loại QR.
     *
     * ── Template Method Pattern (đơn giản hóa) ──────────────────────────────
     * Mỗi loại QR có cách resolve khác nhau:
     *   QrStatic: public_token chính là restaurant_id → query trực tiếp.
     *   QrTable:  public_token là table_id → phải join qua bảng tables.
     *
     * Khi thêm loại QR mới (VD: QrSession) → chỉ thêm case mới vào match,
     * không sửa gì ở trên (Open/Closed).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Nếu token không hợp lệ
     */
    private function resolveRestaurantId(string $publicToken, OrderSourceChannel $channel): string
    {
        return match ($channel) {
            // QR Tĩnh: public_token = restaurant_id, validate bằng findOrFail
            OrderSourceChannel::QrStatic => tap(
                Restaurant::findOrFail($publicToken)->id,
                fn() => null // tap() chỉ để đảm bảo trả về string id
            ),

            // QR Bàn (Pro): public_token = table_id → tìm bàn rồi lấy restaurant_id
            // Table model sẽ được implement khi mở Pro module
            OrderSourceChannel::QrTable => \App\Models\Table::findOrFail($publicToken)->restaurant_id,

            // Các kênh nội bộ không bao giờ vào đây (FormRequest đã chặn)
            default => throw new \DomainException("Kênh [{$channel->value}] không được phép đặt hàng công khai."),
        };
    }
}
