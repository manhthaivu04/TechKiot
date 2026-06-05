<?php

namespace App\Events;

use App\DTOs\PlaceOrderDTO;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * OrderPlaced — Event phát ra sau khi đơn hàng được lưu DB thành công.
 *
 * ── Observer Pattern ─────────────────────────────────────────────────────────
 * Event này là cầu nối "Loose Coupling" giữa PlaceOrderAction và các
 * side-effects (notify bếp, trừ kho...). PlaceOrderAction không cần
 * biết bao nhiêu Listener đang lắng nghe — chỉ cần fire event là xong.
 *
 * Các Listener đăng ký trong EventServiceProvider:
 *   ┌────────────────────────────────────────────────────────────┐
 *   │ [BASIC]   HandleOrderSourceStrategyListener → Trigger QR/POS Strategy
 *   │ [BASIC]   NotifyKitchenListener             → Broadcast xuống màn KDS
 *   │ [PREMIUM] DeductInventoryListener           → Trừ nguyên liệu (check gói)
 *   └────────────────────────────────────────────────────────────┘
 */
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\Channel;

class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order        $order,
        public readonly PlaceOrderDTO $dto,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("restaurant.{$this->order->restaurant_id}.kitchen");
    }

    public function broadcastAs(): string
    {
        return 'OrderPlaced';
    }
}
