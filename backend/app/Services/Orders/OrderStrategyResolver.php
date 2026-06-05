<?php

namespace App\Services\Orders;

use App\Contracts\Orders\OrderSourceStrategy;
use App\Enums\OrderSourceChannel;
use App\Services\Orders\Strategies\CashierOrderStrategy;
use App\Services\Orders\Strategies\QrStaticOrderStrategy;
use App\Services\Orders\Strategies\QrTableOrderStrategy;

/**
 * Resolver ánh xạ OrderSourceChannel → Strategy tương ứng.
 *
 * Tách resolver ra class riêng thay vì để trong OrderService vì:
 * 1. Single Responsibility: OrderService không cần biết cách resolve strategy.
 * 2. Dễ test: mock resolver trong unit test OrderService.
 * 3. Mở rộng: thêm kênh mới chỉ sửa file này — Open/Closed Principle.
 *
 * Sử dụng Laravel container để tự động inject dependencies vào Strategy,
 * thay vì new Class() thủ công.
 */
class OrderStrategyResolver
{
    /**
     * @throws \InvalidArgumentException khi channel chưa có strategy
     */
    public function resolve(OrderSourceChannel $channel): OrderSourceStrategy
    {
        return match ($channel) {
            OrderSourceChannel::Cashier => app(CashierOrderStrategy::class),
            OrderSourceChannel::QrStatic => app(QrStaticOrderStrategy::class),

                // Pro: uncomment khi implement Pro module
            OrderSourceChannel::QrTable => app(QrTableOrderStrategy::class),
        };
    }
}
