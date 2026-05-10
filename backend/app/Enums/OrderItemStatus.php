<?php

namespace App\Enums;

/**
 * Trạng thái từng dòng món trong đơn hàng.
 * Dùng cho màn hình KDS (Kitchen Display System) — Pro: STAFF_MANAGEMENT.
 * Khai báo sẵn để DB schema không phải thay đổi khi nâng cấp gói.
 */
enum OrderItemStatus: string
{
    case Pending   = 'pending';   // Vừa đặt, chưa vào bếp
    case Cooking   = 'cooking';   // Bếp đang làm
    case Ready     = 'ready';     // Bếp xong, chờ phục vụ
    case Served    = 'served';    // Đã mang ra bàn
    case Cancelled = 'cancelled'; // Huỷ (khách đổi ý)
}
