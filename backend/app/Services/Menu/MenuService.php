<?php

namespace App\Services\Menu;

use App\DTOs\GetMenuDTO;

/**
 * MenuService — Điều phối luồng lấy Menu qua QR Code.
 *
 * ── Tương đồng với OrderService ─────────────────────────────────────────────
 * OrderService::placeOrder(PlaceOrderDTO)
 *   → Dùng OrderStrategyResolver.resolve(sourceChannel) → strategy.handle()
 *
 * MenuService::getMenu(GetMenuDTO)
 *   → Dùng MenuStrategyResolver.resolve(type)           → strategy.getMenu()
 *
 * Nguyên tắc thiết kế (giống OrderService):
 * - SRP: chỉ orchestrate — nhận DTO, chọn strategy, trả kết quả.
 * - OCP: thêm loại QR mới không cần sửa class này.
 * - DIP: inject MenuStrategyResolver (không new cứng strategy).
 *
 * MenuService KHÔNG biết:
 * - Làm sao validate token (việc của Strategy).
 * - Query Eloquent thế nào (việc của Repository).
 * - Gom nhóm ra sao (việc của MenuGrouper).
 */
class MenuService
{
    public function __construct(
        private readonly MenuStrategyResolver $strategyResolver,
    ) {}

    /**
     * Lấy danh sách Menu đã được gom nhóm theo Danh mục.
     *
     * ── Workflow (song song với OrderService::placeOrder) ───────────────────
     * 1. Resolver chọn strategy dựa vào dto->type.
     * 2. Strategy tự xử lý validate token + fetch + group.
     * 3. MenuService trả kết quả thô về Controller.
     *
     * @param  GetMenuDTO    $dto
     * @return array<int, mixed> Menu đã group theo category
     */
    public function getMenu(GetMenuDTO $dto): array
    {
        // MenuService không biết và không cần biết strategy làm gì cụ thể.
        // Đây là điểm mở rộng chính của architecture — giống OrderService.
        $strategy = $this->strategyResolver->resolve($dto->type);

        return $strategy->getMenu($dto);
    }
}
