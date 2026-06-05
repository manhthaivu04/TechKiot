<?php

namespace App\Services\Menu;

use App\Contracts\Menu\MenuSourceStrategy;
use App\Enums\MenuSourceType;
use App\Services\Menu\Strategies\QrStaticMenuStrategy;

/**
 * Resolver ánh xạ MenuSourceType → MenuSourceStrategy tương ứng.
 *
 * ── Tương đồng với OrderStrategyResolver ────────────────────────────────────
 * OrderStrategyResolver::resolve(OrderSourceChannel) → OrderSourceStrategy
 * MenuStrategyResolver::resolve(MenuSourceType)      → MenuSourceStrategy
 *
 * Tách resolver ra class riêng thay vì để trong MenuService vì:
 * 1. SRP: MenuService không cần biết cách resolve strategy.
 * 2. Testability: mock resolver khi unit test MenuService.
 * 3. OCP: thêm loại QR mới chỉ sửa file này — không sửa MenuService.
 *
 * Dùng Laravel container (app()) để inject dependencies vào Strategy
 * tự động, thay vì new Class() thủ công.
 */
class MenuStrategyResolver
{
    /**
     * @throws \ValueError Khi type chưa có strategy (enum không map được)
     */
    public function resolve(MenuSourceType $type): MenuSourceStrategy
    {
        return match ($type) {
            MenuSourceType::QrStatic => app(QrStaticMenuStrategy::class),

            // Pro: uncomment khi implement Pro module
            // MenuSourceType::QrTable => app(QrTableMenuStrategy::class),
        };
    }
}
