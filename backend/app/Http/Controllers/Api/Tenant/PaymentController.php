<?php

namespace App\Http\Controllers\Api\Tenant;

use App\Contracts\Services\PaymentServiceInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\RecordPaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * PaymentController — Điều phối HTTP cho module Thanh toán.
 *
 * ── Trách nhiệm ───────────────────────────────────────────────────────────────
 * 1. Nhận Request (đã validated bởi RecordPaymentRequest).
 * 2. Build RecordPaymentDTO.
 * 3. Gọi PaymentService.
 * 4. Trả về PaymentResource.
 *
 * ── Tại sao tách khỏi OrderController? ───────────────────────────────────────
 * OrderController hiện đang phình to với cả 8 action (CRUD order + item + payment).
 * PaymentController giữ đúng 1 trách nhiệm: nghiệp vụ "dòng tiền".
 * OrderController giờ chỉ lo "vòng đời đơn hàng".
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentServiceInterface $paymentService,
    ) {}

    /**
     * POST /api/tenant/orders/{id}/payments
     * Ghi nhận một lần thanh toán (hỗ trợ split payment).
     */
    public function store(RecordPaymentRequest $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $dto = RecordPaymentDTO::fromArray(
            orderId:   $order->id,
            createdBy: $user->id,
            data:      $request->validated(),
        );

        $payment = $this->paymentService->record($order, $dto);

        return $this->successResponse(
            data:       new PaymentResource($payment),
            message:    'Ghi nhận thanh toán thành công.',
            code:       \App\Enums\ApiCode::CREATED,
            httpStatus: 201,
        );
    }

    /**
     * GET /api/tenant/orders/{id}/payments
     * Lấy lịch sử thanh toán của một đơn hàng.
     */
    public function index(string $id): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $order = Order::query()
            ->where('restaurant_id', $user->restaurant_id)
            ->findOrFail($id);

        $payments = $this->paymentService->listByOrder($order);

        return $this->successResponse(
            data: PaymentResource::collection($payments),
        );
    }
}
