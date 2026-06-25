<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;

/**
 * TransferGateway — Strategy xử lý thanh toán chuyển khoản ngân hàng.
 *
 * Tương tự Card: yêu cầu reference_no để đối soát giao dịch ngân hàng.
 * Trong tương lai có thể tích hợp với VietQR/BIDV API để tự động xác thực.
 */
class TransferGateway implements PaymentGatewayInterface
{
    public function validate(RecordPaymentDTO $dto): void
    {
        if (empty($dto->referenceNo)) {
            throw new \DomainException(
                'Thanh toán chuyển khoản yêu cầu mã giao dịch (reference_no) để đối soát.'
            );
        }
    }

    public function prepare(Order $order, RecordPaymentDTO $dto): ?array
    {
        // Phase 1: Nhân viên xác nhận thủ công.
        // Phase 2 (Premium): Gọi VietQR / Bank API để auto-verify.
        return null;
    }

    public function label(): string
    {
        return 'Chuyển khoản ngân hàng';
    }
}
