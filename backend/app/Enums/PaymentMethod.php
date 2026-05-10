<?php

namespace App\Enums;

/**
 * Phương thức thanh toán được hỗ trợ.
 * Enum backed string để dễ store/retrieve từ DB và cast tự động.
 */
enum PaymentMethod: string
{
    case Cash     = 'cash';
    case Card     = 'card';
    case Transfer = 'transfer';
    case Ewallet  = 'ewallet'; // MoMo, ZaloPay, v.v.

    public function label(): string
    {
        return match ($this) {
            self::Cash     => 'Tiền mặt',
            self::Card     => 'Thẻ ngân hàng',
            self::Transfer => 'Chuyển khoản',
            self::Ewallet  => 'Ví điện tử',
        };
    }
}
