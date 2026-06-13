<?php

namespace App\Services\Orders\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Enums\OrderStatus;
use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\DB;

/**
 * RecordPaymentAction — Ghi nhận thanh toán cho một đơn hàng.
 *
 * ── Single Responsibility ────────────────────────────────────────────────────
 * Class này chỉ làm DUY NHẤT một việc:
 * 1. Tạo Payment record.
 * 2. Tự động kiểm tra đã đủ tiền chưa → chuyển Order sang Paid.
 *
 * Tách khỏi PlaceOrderAction vì:
 * - Vòng đời khác nhau: khách ăn xong mới thanh toán (có thể cách nhau 2 tiếng).
 * - Hỗ trợ Split Payment: gọi hàm này nhiều lần cho cùng 1 Order
 *   (trả nửa tiền mặt, nửa chuyển khoản) mà không ảnh hưởng logic tạo đơn.
 */
class RecordPaymentAction
{
    /**
     * Thực thi ghi nhận thanh toán.
     *
     * @param Order       $order       Đơn hàng cần thanh toán
     * @param float|null  $amount      Số tiền thanh toán lần này (nếu null, tự động thanh toán toàn bộ phần còn lại)
     * @param string      $method      Phương thức (cash | card | transfer | ewallet)
     * @param string      $createdBy   ID nhân viên thực hiện
     * @param string|null $referenceNo Mã giao dịch ngân hàng/ví (optional)
     *
     * @throws \DomainException Nếu Order đã ở trạng thái không cho phép thanh toán
     */
    public function execute(
        Order   $order,
        ?float  $amount,
        string  $method,
        string  $createdBy,
        ?string $referenceNo = null,
    ): Payment {
        $payment = DB::transaction(function () use ($order, $amount, $method, $createdBy, $referenceNo): Payment {

            // BẢO MẬT: Không bao giờ tin tưởng Client
            $totalPaid = $order->payments()->sum('amount');
            $remaining = $order->final_amount - $totalPaid;

            if ($remaining <= 0) {
                throw new \DomainException('Đơn hàng này đã được thanh toán đủ.');
            }

            // Nếu client gửi số tiền > số tiền còn lại, chặn luôn
            if ($amount !== null && $amount > $remaining) {
                throw new \DomainException("Số tiền thanh toán ($amount) vượt quá số tiền cần phải thu ($remaining).");
            }

            // Nếu client không truyền amount, mặc định là thanh toán toàn bộ phần còn lại
            $finalAmountToPay = $amount ?? $remaining;

            $payment = Payment::create([
                'order_id'       => $order->id,
                'amount'         => $finalAmountToPay,
                'payment_method' => $method,
                'reference_no'   => $referenceNo,
                'paid_at'        => now(),
                'created_by'     => $createdBy,
            ]);

            // Reload để sum() tính đúng payment vừa insert
            $order->refresh();

            // isPaidInFull() tổng hợp TẤT CẢ payments — hỗ trợ split payment.
            // Không cần sửa logic này khi thêm phương thức thanh toán mới.
            if ($order->isPaidInFull()) {
                $order->transitionTo(OrderStatus::Paid);
            }

            return $payment;
        });

        // Bắn Event để kích hoạt PrintReceiptListener và các Listener khác SAU KHI commit
        PaymentRecorded::dispatch($order->refresh(), $payment);

        return $payment;
    }
}
