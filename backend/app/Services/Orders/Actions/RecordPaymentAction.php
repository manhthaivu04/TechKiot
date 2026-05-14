<?php

namespace App\Services\Orders\Actions;

use App\Models\Order;
use App\Models\Payment;
use App\Enums\OrderStatus;
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
     * @param float       $amount      Số tiền thanh toán lần này
     * @param string      $method      Phương thức (cash | card | transfer | ewallet)
     * @param string      $createdBy   ID nhân viên thực hiện
     * @param string|null $referenceNo Mã giao dịch ngân hàng/ví (optional)
     *
     * @throws \DomainException Nếu Order đã ở trạng thái không cho phép thanh toán
     */
    public function execute(
        Order   $order,
        float   $amount,
        string  $method,
        string  $createdBy,
        ?string $referenceNo = null,
    ): Payment {
        return DB::transaction(function () use ($order, $amount, $method, $createdBy, $referenceNo): Payment {

            $payment = Payment::create([
                'order_id'       => $order->id,
                'amount'         => $amount,
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
    }
}
