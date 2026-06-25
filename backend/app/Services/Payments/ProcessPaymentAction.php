<?php

namespace App\Services\Payments;

use App\DTOs\Payments\RecordPaymentDTO;
use App\Events\PaymentRecorded;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\RestaurantPaymentMethod;
use Illuminate\Support\Facades\DB;

/**
 * ProcessPaymentAction — Orchestrator ghi nhận thanh toán.
 *
 * ── Luồng xử lý ──────────────────────────────────────────────────────────────
 * 1. Kiểm tra nghiệp vụ chung (đơn đã đủ tiền chưa, số tiền có hợp lệ không).
 * 2. Gọi Gateway Strategy tương ứng để validate() logic đặc thù.
 * 3. Gọi Gateway.prepare() để xử lý tác vụ ngoài (VD: tạo payment intent).
 * 4. Lưu Payment record vào DB.
 * 5. Kiểm tra isPaidInFull() → chuyển Order sang Paid nếu đủ.
 * 6. Fire PaymentRecorded event SAU KHI commit.
 *
 * ── Tại sao tách khỏi RecordPaymentAction cũ? ────────────────────────────────
 * RecordPaymentAction cũ chứa tất cả trong 1 class và không có Gateway Strategy.
 * Class mới này ủy quyền logic đặc thù cho từng Gateway, giữ mình chỉ là
 * "người điều phối" — đúng vai trò Orchestrator.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Thêm cổng thanh toán mới → thêm Gateway class mới + thêm case vào Factory.
 * Class này KHÔNG cần thay đổi.
 */
class ProcessPaymentAction
{
    /**
     * @throws \DomainException Nếu vi phạm business rule (đơn đã đủ tiền, số tiền sai, v.v.)
     */
    public function execute(Order $order, RecordPaymentDTO $dto): Payment
    {
        // ── Kiểm tra phương thức thanh toán có đang được bật không ────────────────
        // OWNER/MANAGER có thể tắt từng phương thức qua API payment-method-settings.
        $config = RestaurantPaymentMethod::where('restaurant_id', $order->restaurant_id)
            ->where('payment_method', $dto->method->value)
            ->first();

        // Nếu có config và is_active = false → chặn ngay, không tiếp tục
        if ($config !== null && ! $config->is_active) {
            throw new \DomainException(
                "Phương thức thanh toán [{$dto->method->label()}] hiện đang bị tắt."
                . ' Vui lòng liên hệ quản lý để bật lại.'
            );
        }

        // ── Chọn đúng Gateway Strategy dựa vào payment method ─────────────────
        $gateway = PaymentGatewayFactory::make($dto->method);

        // ── Validate logic đặc thù của từng Gateway ───────────────────────────
        // VD: Card/Transfer/Ewallet yêu cầu reference_no bắt buộc.
        $gateway->validate($dto);

        $payment = DB::transaction(function () use ($order, $dto, $gateway): Payment {

            // ── Validate nghiệp vụ chung ──────────────────────────────────────
            $totalPaid = $order->payments()->sum('amount');
            $remaining = $order->final_amount - $totalPaid;

            if ($remaining <= 0) {
                throw new \DomainException('Đơn hàng này đã được thanh toán đủ.');
            }

            if ($dto->amount !== null && $dto->amount > $remaining) {
                throw new \DomainException(
                    "Số tiền thanh toán ({$dto->amount}) vượt quá số tiền cần phải thu ({$remaining})."
                );
            }

            $finalAmountToPay = $dto->amount ?? $remaining;

            // ── Gateway prepare: xử lý tác vụ ngoài (nếu có) ────────────────
            // VD: EwalletGateway ở Phase 2 sẽ gọi API MoMo tại đây.
            $extraData = $gateway->prepare($order, $dto) ?? [];

            // ── Lưu Payment record ────────────────────────────────────────────
            $payment = Payment::create(array_merge([
                'order_id'       => $order->id,
                'amount'         => $finalAmountToPay,
                'payment_method' => $dto->method->value,
                'reference_no'   => $dto->referenceNo,
                'paid_at'        => now(),
                'created_by'     => $dto->createdBy,
            ], $extraData));

            // Reload để sum() tính đúng payment vừa insert
            $order->refresh();

            // ── Auto-transition Order sang Paid nếu đã đủ tiền ───────────────
            // isPaidInFull() hỗ trợ split payment: tổng TẤT CẢ payments ≥ final_amount.
            if ($order->isPaidInFull() && $order->status !== OrderStatus::Paid) {
                $order->transitionTo(OrderStatus::Paid);
            }

            return $payment;
        });

        // ── Fire Event SAU KHI transaction commit ─────────────────────────────
        // Listeners: PrintReceiptListener, BroadcastPaymentSuccessListener, v.v.
        PaymentRecorded::dispatch($order->refresh(), $payment);

        return $payment;
    }
}
