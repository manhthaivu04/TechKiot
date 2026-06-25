<?php

namespace App\Enums;

/**
 * Loại nguồn QR dùng để lấy Menu.
 * Mỗi giá trị tương ứng với một MenuSourceStrategy — Strategy Pattern.
 *
 * ── Tương đồng với OrderSourceChannel ──────────────────────────────────────
 * OrderSourceChannel::QrStatic  → QrStaticOrderStrategy  (tạo đơn)
 * MenuSourceType::QrStatic      → QrStaticMenuStrategy   (lấy menu)
 *
 * Thêm loại QR mới (e.g., qr_table cho Pro) chỉ cần:
 * 1. Thêm case vào enum này.
 * 2. Tạo class strategy tương ứng.
 * 3. Đăng ký trong MenuStrategyResolver.
 * → Không cần sửa MenuService — Open/Closed Principle.
 */
enum MenuSourceType: string
{
    // Basic: QR tĩnh đính kèm restaurant_id
    case QrStatic = 'qr_static';

    // Pro: QR động đính kèm table_id
    case QrTable = 'qr_table';
}
