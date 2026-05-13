<?php

namespace App\Contracts\Menu;

use App\DTOs\GetMenuDTO;

/**
 * Strategy Interface cho các loại nguồn QR lấy Menu.
 *
 * ── Tương đồng với OrderSourceStrategy ──────────────────────────────────────
 * OrderSourceStrategy::handle(Order, PlaceOrderDTO): void
 *   → Mỗi kênh đặt đơn xử lý side-effects khác nhau sau khi tạo đơn.
 *
 * MenuSourceStrategy::getMenu(GetMenuDTO): array
 *   → Mỗi loại QR xử lý logic resolve nhà hàng/bàn và trả về menu.
 *
 * Open/Closed Principle tại đây:
 * - Đóng: MenuService không thay đổi khi thêm loại QR mới.
 * - Mở: Tạo class mới implement interface này là đủ.
 *
 * Ví dụ mở rộng Pro:
 *   class QrTableMenuStrategy implements MenuSourceStrategy {
 *       // Resolve restaurant_id từ table_id, trả menu + thông tin bàn
 *   }
 */
interface MenuSourceStrategy
{
    /**
     * Lấy và trả về danh sách menu đã được format theo từng loại QR.
     *
     * Mỗi Strategy tự chịu trách nhiệm:
     * 1. Validate/resolve token → restaurant_id (hoặc table_id).
     * 2. Gọi ItemRepository lấy items đang active.
     * 3. Gom nhóm items theo group_item qua MenuGrouper.
     *
     * @param  GetMenuDTO       $dto  Chứa public_token và type
     * @return array<int, mixed>      Menu đã được group theo danh mục
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException  Token không hợp lệ
     */
    public function getMenu(GetMenuDTO $dto): array;
}
