<?php

namespace App\Enums;

/**
 * Kênh tạo đơn hàng.
 * Mỗi giá trị tương ứng với một OrderSourceStrategy — Strategy Pattern.
 *
 * Thêm kênh mới (e.g., qr_table cho Pro) chỉ cần:
 * 1. Thêm case vào enum này.
 * 2. Tạo class strategy tương ứng.
 * 3. Đăng ký trong OrderStrategyResolver.
 * → Không cần sửa OrderService — Open/Closed Principle.
 */
enum OrderSourceChannel: string
{
    // Basic
    case Cashier   = 'cashier';    // POS_QUICK_ORDER
    case QrStatic  = 'qr_static';  // QR_STATIC_ORDER

    // Pro (chỉ uncomment khi implement Pro module)
    case QrTable   = 'qr_table';   // QR_TABLE_ORDER

    // Waiter app (tương lai)
    // case Waiter = 'waiter';
}
