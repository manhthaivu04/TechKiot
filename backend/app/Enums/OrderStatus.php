<?php

namespace App\Enums;

/**
 * OrderStatus — State Machine cho vòng đời đơn hàng.
 *
 * Sử dụng State Pattern ngay trong Enum để giữ logic chuyển trạng thái
 * gần với dữ liệu nhất có thể (cohesion cao). Service chỉ cần gọi
 * `canTransitionTo()` mà không cần biết rule cụ thể — tuân thủ SRP.
 *
 * Sơ đồ hợp lệ (Basic):
 *   open ──► cooking ──► served ──► paid
 *     │          │          │
 *     └──────────┴──────────┴──► cancelled
 *
 * Gói Pro (sau này) có thể thêm trạng thái như `ready` (bếp xong, chờ serve)
 * mà chỉ cần mở rộng canTransitionTo() — không phá vỡ luồng hiện tại.
 */
enum OrderStatus: string
{
    case Open      = 'open';
    case Cooking   = 'cooking';
    case Served    = 'served';
    case Paid      = 'paid';
    case Cancelled = 'cancelled';

}
