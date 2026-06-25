<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;

/**
 * CardGateway — Strategy xử lý thanh toán thẻ ngân hàng (POS).
 *
 * Yêu cầu reference_no (mã giao dịch từ máy POS) để đối soát.
 * Không cần tích hợp API ngoài — nhân viên nhập thủ công.
 */
class CardGateway implements PaymentGatewayInterface
{
    public function validate(RecordPaymentDTO $dto): void
    {
        if (empty($dto->referenceNo)) {
            throw new \DomainException(
                'Thanh toán bằng thẻ yêu cầu mã giao dịch (reference_no) từ máy POS.'
            );
        }
    }

    public function prepare(Order $order, RecordPaymentDTO $dto): ?array
    {
        // Không cần gọi API ngoài — nhân viên đã quẹt thẻ trực tiếp tại quầy.
        return null;
    }

    public function label(): string
    {
        return 'Thẻ ngân hàng';
    }
}
