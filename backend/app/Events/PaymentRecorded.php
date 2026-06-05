<?php

namespace App\Events;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * PaymentRecorded — Event phát ra sau khi một lần thanh toán được ghi nhận.
 *
 * ── Observer Pattern ─────────────────────────────────────────────────────────
 * RecordPaymentAction chỉ biết "đã ghi nhận thanh toán" và fire event này.
 * Các side-effects (in hóa đơn, thông báo,...) được xử lý hoàn toàn bởi Listeners.
 *
 * Các Listener đăng ký trong EventServiceProvider:
 *   ┌──────────────────────────────────────────────────────────────┐
 *   │ [BASIC]   PrintReceiptListener  → Log hóa đơn (in PDF sau)  │
 *   │ [PRO]     NotifyCustomerListener → Gửi SMS/Email cho khách  │
 *   └──────────────────────────────────────────────────────────────┘
 *
 * ── Broadcasting ─────────────────────────────────────────────────────────────
 * Broadcast vào kênh của đơn hàng để Frontend (màn hình thu ngân)
 * biết đơn hàng vừa được thanh toán mà không cần F5.
 */
class PaymentRecorded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order   $order,
        public readonly Payment $payment,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("order.{$this->order->id}");
    }

    public function broadcastAs(): string
    {
        return 'PaymentRecorded';
    }
}
