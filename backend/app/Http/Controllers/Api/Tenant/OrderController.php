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

        return response()->json([
            'message' => 'Đặt đơn hàng thành công.',
            'data'    => new OrderResource($order),
        ], 201);
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
            ->with(['items.item', 'payments'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
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

        return response()->json(['data' => new OrderResource($order)]);
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

        $newStatus = OrderStatus::from(request()->input('status'));
        $order     = $this->orderService->transition($order, $newStatus);

        return response()->json([
            'message' => "Đơn hàng đã chuyển sang trạng thái [{$newStatus->label()}].",
            'data'    => new OrderResource($order),
        ]);
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
            amount:      (float) $validated['amount'],
            method:      $validated['payment_method'],
            createdBy:   $user->id,
            referenceNo: $validated['reference_no'] ?? null,
        );

        return response()->json([
            'message' => 'Ghi nhận thanh toán thành công.',
            'data'    => new PaymentResource($payment),
        ], 201);
    }
}
