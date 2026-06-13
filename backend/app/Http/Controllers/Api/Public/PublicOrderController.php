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
 * HIỆN CHỈ HỖ TRỢ kênh `qr_static` (QR tĩnh nhà hàng).
 * Kênh `qr_table` (QR theo bàn — gói Pro) chưa kích hoạt ở public order vì
 * qr_token sinh dạng `tbl_{uuid}` và luồng gán order vào bàn chưa hoàn thiện.
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
 *    PlaceOrderDTO chứa source_channel = qr_static.
 *    PlaceOrderAction sẽ dựa vào đây để kích hoạt đúng Strategy:
 *    → QrStaticOrderStrategy: bắn thông báo real-time cho nhân viên.
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
 *   - source_channel: chỉ qr_static (FormRequest đã chặn cashier + qr_table)
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
     *   - public_token:   UUID (restaurant_id — từ QR tĩnh)
     *   - source_channel: string (chỉ qr_static)
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
        // Hiện tại chỉ có QrStatic: public_token = restaurant_id (UUID).
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

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đặt món thành công! Đơn hàng của bạn đang được chuẩn bị.',
            code:    \App\Enums\ApiCode::CREATED,
            httpStatus: 201
        );
    }

    /**
     * GET /api/public/orders/{id}
     * Cho phép khách xem lại trạng thái đơn hàng của mình (thông qua UUID).
     */
    public function show(string $id): JsonResponse
    {
        // Vì UUID rất khó đoán nên có thể dùng trực tiếp để tra cứu đơn hàng công khai.
        $order = \App\Models\Order::query()
            ->with(['items.item', 'payments'])
            ->findOrFail($id);

        return $this->successResponse(new OrderResource($order));
    }

    // ─── Private Helpers ──────────────────────────────────────────────────────

    /**
     * POST /api/public/orders/{id}/items
     * Cho phép khách gọi thêm món vào đơn hàng hiện tại.
     */
    public function addItems(\App\Http\Requests\Order\AddOrderItemsRequest $request, string $id): JsonResponse
    {
        $validated = $request->validated();

        $order = \App\Models\Order::query()->findOrFail($id);

        // Chuyển array thành DTO
        $newItems = array_map(fn($item) => new \App\DTOs\PlaceOrderItemDTO(
            itemId: $item['item_id'],
            quantity: $item['quantity'],
            note: $item['note'] ?? null,
        ), $validated['items']);

        // Gọi qua OrderService, dùng chung DTO và Pipeline của chức năng gọi thêm món
        $order = $this->orderService->addItems($order, $newItems);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đã gọi thêm món thành công!',
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }

    /**
     * Resolve restaurant_id từ public_token.
     *
     * Hiện tại chỉ hỗ trợ QrStatic (public_token = restaurant_id dạng UUID).
     *
     * QrTable chưa kích hoạt vì:
     *   - qr_token bàn sinh dạng `tbl_{uuid}` (không phải UUID thuần).
     *   - Luồng gán order vào bàn + update trạng thái bàn chưa implement.
     *   - FormRequest đã chặn source_channel=qr_table → nhánh này không thể vào.
     * Khi module QR bàn sẵn sàng, thêm lại case QrTable ở đây.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Nếu token không hợp lệ
     * @throws \DomainException Nếu kênh không được hỗ trợ
     */
    private function resolveRestaurantId(string $publicToken, OrderSourceChannel $channel): string
    {
        return match ($channel) {
            // QR Tĩnh: public_token = public_order_token, validate bằng firstOrFail
            OrderSourceChannel::QrStatic => tap(
                Restaurant::where('public_order_token', $publicToken)->firstOrFail()->id,
                fn() => null // tap() chỉ để đảm bảo trả về string id
            ),

            // Các kênh nội bộ không bao giờ vào đây (FormRequest đã chặn)
            default => throw new \DomainException("Kênh [{$channel->value}] không được phép đặt hàng công khai."),
        };
    }
}
