<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use Illuminate\Support\Facades\Log;

/**
 * HandleOrderSourceStrategyListener [BASIC]
 *
 * ── Nhiệm vụ ─────────────────────────────────────────────────────────────────
 * Sau khi OrderPlaced được fire, Listener này trigger đúng OrderSourceStrategy
 * tương ứng với source_channel của đơn hàng (QR Static, POS, Cashier...).
 *
 * Đây là "cầu nối" giữa Observer Pattern và Strategy Pattern.
 * PlaceOrderAction → fire Event → Listener này → gọi Strategy.
 *
 * ── Lý do tách ra Listener ───────────────────────────────────────────────────
 * Trước đây Strategy được gọi trực tiếp trong PlaceOrderAction.
 * Khi tách ra, PlaceOrderAction không còn phụ thuộc vào StrategyResolver nữa.
 * Muốn disable Strategy cho môi trường test → chỉ cần unregister Listener.
 */
class HandleOrderSourceStrategyListener
{
    public function __construct(
        private readonly \App\Services\Orders\OrderStrategyResolver $strategyResolver,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $strategy = $this->strategyResolver->resolve($event->order->source_channel);
        $strategy->handle($event->order, $event->dto);

        Log::debug("Strategy [{$event->order->source_channel->value}] handled for Order [{$event->order->order_code}].");
    }
}
