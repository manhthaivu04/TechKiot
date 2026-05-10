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

    /**
     * Kiểm soát vòng đời đơn hàng theo State Pattern.
     *
     * Lý do không dùng mảng constant: method cho phép mở rộng logic
     * phức tạp hơn (e.g., check điều kiện runtime) mà không phải
     * thay đổi caller code — tuân thủ Open/Closed Principle.
     */
    public function canTransitionTo(OrderStatus $newStatus): bool
    {
        return match ($this) {
            // Trạng thái mở đầu: có thể vào bếp hoặc huỷ ngay
            self::Open => in_array($newStatus, [
                self::Cooking,
                self::Cancelled,
            ], true),

            // Đang nấu: bếp xong thì served, hoặc huỷ nếu cần
            self::Cooking => in_array($newStatus, [
                self::Served,
                self::Cancelled,
            ], true),

            // Đã phục vụ: chỉ được thanh toán hoặc huỷ
            self::Served => in_array($newStatus, [
                self::Paid,
                self::Cancelled,
            ], true),

            // Trạng thái cuối — không thể chuyển tiếp
            self::Paid,
            self::Cancelled => false,
        };
    }

    /**
     * Trả về label tiếng Việt để hiển thị trên UI — tránh hardcode string
     * rải rác ở nhiều nơi trong codebase.
     */
    public function label(): string
    {
        return match ($this) {
            self::Open      => 'Đang mở',
            self::Cooking   => 'Đang nấu',
            self::Served    => 'Đã phục vụ',
            self::Paid      => 'Đã thanh toán',
            self::Cancelled => 'Đã huỷ',
        };
    }

    /**
     * Kiểm tra đơn có thể chỉnh sửa (thêm/xoá món) không.
     * Chỉ đơn đang `open` mới được sửa — tránh kiểm tra hardcode string
     * ở nhiều Controller.
     */
    public function isEditable(): bool
    {
        return $this === self::Open;
    }
}
