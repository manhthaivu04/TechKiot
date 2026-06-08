<?php

namespace App\Enums;

/**
 * TableStatus — trạng thái của bàn ăn.
 *
 * Sau này có thể mở rộng thêm trạng thái (vd: `cleaning`, `merged`)
 * mà không ảnh hưởng logic hiện tại — tuân thủ OCP.
 */
enum TableStatus: string
{
    case Available = 'available';
    case Occupied  = 'occupied';
    case Reserved  = 'reserved';
    case Inactive  = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Trống',
            self::Occupied  => 'Đang sử dụng',
            self::Reserved  => 'Đã đặt trước',
            self::Inactive  => 'Ngưng hoạt động',
        };
    }
}
