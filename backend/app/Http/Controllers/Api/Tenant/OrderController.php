<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Services\OrderServiceInterface;
use App\DTOs\PlaceOrderDTO;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\RecordPaymentRequest;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * OrderController — Điều phối HTTP, không chứa business logic.
 *
 * Trách nhiệm:
 * 1. Nhận Request (đã validated)
 * 2. Xây dựng DTO
 * 3. Gọi Service
 * 4. Trả Response
 *
 * Tất cả logic nằm trong OrderService — Controller không quyết định
 * bất kỳ rule business nào (tuân thủ SRP).
 */
class OrderController extends Controller
{
    public function __construct(
        private readonly OrderServiceInterface $orderService,
    ) {}

    /**
     * POST /api/tenant/orders
     * Feature required: POS_QUICK_ORDER hoặc QR_STATIC_ORDER (tuỳ source_channel)
     */
    public function store(PlaceOrderRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $dto = PlaceOrderDTO::fromArray(
            restaurantId: $user->restaurant_id,
            createdBy:    $user->id,
            data:         $request->validated(),
        );

        $order = $this->orderService->placeOrder($dto);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đặt đơn hàng thành công.',
            code:    \App\Enums\ApiCode::CREATED,
            httpStatus: 201
        );
    }

    /**
     * GET /api/tenant/orders
     */
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $orders = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->when(request('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when(request('service_type'), function ($q, $type) {
                $q->where('service_type', $type);
            })
            ->when(request('search'), function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('order_code', 'LIKE', "%{$search}%")
                       ->orWhere('customer_name', 'LIKE', "%{$search}%")
                       ->orWhere('customer_phone', 'LIKE', "%{$search}%");
                });
            })
            ->with(['items.item', 'payments'])
            ->latest()
            ->paginate(50); // Increased pagination slightly for kanban view

        return $this->successResponse([
            'items' => OrderResource::collection($orders->items()),
            'meta'  => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
            ],
        ]);
    }

    /**
     * GET /api/tenant/orders/{id}
     */
    public function show(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->with(['items.item', 'payments'])
            ->findOrFail($id);

        return $this->successResponse(new OrderResource($order));
    }

    /**
     * PATCH /api/tenant/orders/{id}/status
     * Body: { "status": "cooking" }
     */
    public function updateStatus(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $validated = request()->validate([
            'status' => ['required', \Illuminate\Validation\Rule::enum(OrderStatus::class)],
        ]);

        $newStatus = OrderStatus::from($validated['status']);
        $order     = $this->orderService->transition($order, $newStatus);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: "Đơn hàng đã chuyển sang trạng thái [{$order->state()->label()}].",
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }

    /**
     * POST /api/tenant/orders/{id}/payments
     */
    public function storePayment(RecordPaymentRequest $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $validated = $request->validated();

        $payment = $this->orderService->recordPayment(
            order:       $order,
            amount:      isset($validated['amount']) ? (float) $validated['amount'] : null,
            method:      $validated['payment_method'],
            createdBy:   $user->id,
            referenceNo: $validated['reference_no'] ?? null,
        );

        return $this->successResponse(
            data:    new PaymentResource($payment),
            message: 'Ghi nhận thanh toán thành công.',
            code:    \App\Enums\ApiCode::CREATED,
            httpStatus: 201
        );
    }

    /**
     * POST /api/tenant/orders/{id}/items
     * Gọi thêm món vào đơn hàng hiện tại
     */
    public function addItems(\App\Http\Requests\Order\AddOrderItemsRequest $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $validated = $request->validated();
        
        // Chuyển array thành DTO hoặc mảng DTO
        $newItems = array_map(fn($item) => new \App\DTOs\PlaceOrderItemDTO(
            itemId: $item['item_id'],
            quantity: $item['quantity'],
            note: $item['note'] ?? null,
        ), $validated['items']);

        $order = $this->orderService->addItems($order, $newItems);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đã thêm món vào đơn hàng thành công.',
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }
    /**
     * PATCH /api/tenant/orders/{id}/items/{itemId}
     * Cập nhật số lượng / ghi chú của 1 món
     */
    public function updateItem(\App\Http\Requests\Order\UpdateOrderItemRequest $request, string $id, string $itemId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $order = $this->orderService->updateItem($order, $itemId, $request->validated());

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đã cập nhật món ăn thành công.',
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }

    /**
     * DELETE /api/tenant/orders/{id}/items/{itemId}
     * Hủy món khỏi đơn hàng
     */
    public function removeItem(string $id, string $itemId): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $order = $this->orderService->removeItem($order, $itemId);

        return $this->successResponse(
            data:    new OrderResource($order),
            message: 'Đã hủy món thành công.',
            code:    \App\Enums\ApiCode::SUCCESS
        );
    }
}
