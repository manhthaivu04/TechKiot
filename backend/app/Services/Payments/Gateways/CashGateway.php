<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;

/**
 * CashGateway — Strategy xử lý thanh toán tiền mặt.
 *
 * Phương thức đơn giản nhất: không cần xác thực thêm, không cần reference_no.
 * Đây là fallback mặc định cho mọi quán nhà hàng.
 */
class CashGateway implements PaymentGatewayInterface
{
    public function validate(RecordPaymentDTO $dto): void
    {
        // Tiền mặt không cần validate thêm — nhân viên tự kiểm đếm tại quầy.
    }

    public function prepare(Order $order, RecordPaymentDTO $dto): ?array
    {
        // Không cần xử lý gì thêm — ProcessPaymentAction sẽ lo phần lưu DB.
        return null;
    }

    public function label(): string
    {
        return 'Tiền mặt';
    }
}
