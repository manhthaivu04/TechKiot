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
        $channel   = OrderSourceChannel::from($validated['source_channel']);

        // ── Resolve restaurant_id từ public_token (tùy kênh) ────────────────
        ['restaurantId' => $restaurantId, 'tableId' => $tableId] = $this->resolveContext(
            publicToken: $validated['public_token'],
            channel:     $channel,
        );

        // ── Build DTO, gắn thêm tableId nếu là qr_table ─────────────────────
        $validated['table_id'] = $tableId;

        $dto = PlaceOrderDTO::fromArray(
            restaurantId: $restaurantId,
            createdBy:    'system_qr',
            data:         $validated,
        );

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

        $newItems = array_map(fn($item) => new \App\DTOs\PlaceOrderItemDTO(
            itemId: $item['item_id'],
            quantity: $item['quantity'],
            note: $item['note'] ?? null,
        ), $validated['items']);

        $order = $this->orderService->addItems($order, $newItems);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đã gọi thêm món thành công!',
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }

    /**
     * Resolve restaurant_id (và table_id nếu là QrTable) từ public_token.
     *
     * @return array{restaurantId: string, tableId: string|null}
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Nếu token không hợp lệ
     * @throws \DomainException Nếu kênh không được hỗ trợ
     */
    private function resolveContext(string $publicToken, OrderSourceChannel $channel): array
    {
        return match ($channel) {
            // QR tĩnh: public_token = public_order_token của nhà hàng
            OrderSourceChannel::QrStatic => [
                'restaurantId' => \App\Models\Restaurant::where('public_order_token', $publicToken)
                    ->firstOrFail()->id,
                'tableId' => null,
            ],

            // QR bàn: public_token = qr_token của bàn → resolve cả restaurant_id lẫn table_id
            OrderSourceChannel::QrTable => $this->resolveQrTableContext($publicToken),

            default => throw new \DomainException("Kênh [{$channel->value}] không được phép đặt hàng công khai."),
        };
    }

    /**
     * @return array{restaurantId: string, tableId: string}
     */
    private function resolveQrTableContext(string $publicToken): array
    {
        $table = \App\Models\RestaurantTable::with('restaurant')->where('qr_token', $publicToken)->firstOrFail();

        if (! $table->restaurant->hasFeature('TABLE_MANAGEMENT')) {
            abort(403, 'Tính năng đặt món tại bàn hiện đang bị khóa.');
        }

        return [
            'restaurantId' => $table->restaurant_id,
            'tableId'      => $table->id,
        ];
    }
}
