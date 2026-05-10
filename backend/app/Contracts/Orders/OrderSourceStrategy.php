<?php

namespace App\Contracts\Orders;

use App\DTOs\PlaceOrderDTO;
use App\Models\Order;

/**
 * Strategy Interface cho các kênh tạo đơn hàng.
 *
 * Open/Closed Principle tại đây:
 * - Đóng: OrderService không thay đổi khi thêm kênh mới.
 * - Mở: Tạo class mới implement interface này là đủ.
 *
 * Ví dụ mở rộng Pro:
 *   class QrTableOrderStrategy implements OrderSourceStrategy {
 *       // validate table_id, assign order to table, notify waiter app...
 *   }
 */
interface OrderSourceStrategy
{
    /**
     * Thực thi logic đặc thù của từng kênh SAU KHI order đã được tạo.
     *
     * Ví dụ:
     * - CashierStrategy: không cần làm gì thêm (POS trực tiếp).
     * - QrStaticStrategy: gửi thông báo cho nhân viên có đơn mới.
     * - QrTableStrategy (Pro): gắn order vào table record, cập nhật table status.
     *
     * @param Order        $order Đơn hàng vừa được tạo (đã persist)
     * @param PlaceOrderDTO $dto  DTO gốc, strategy có thể đọc thêm context
     */
    public function handle(Order $order, PlaceOrderDTO $dto): void;
}
