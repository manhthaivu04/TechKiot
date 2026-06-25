<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\DTOs\Payments\RecordPaymentDTO;
use App\Models\Order;

/**
 * EwalletGateway — Strategy xử lý thanh toán ví điện tử (MoMo, ZaloPay, v.v.).
 *
 * Phase 1: Nhân viên nhập reference_no sau khi khách quét QR ví tự phát.
 *
 * Phase 2 (Premium Feature):
 * - Tích hợp MoMo API / ZaloPay API để generate dynamic QR per-order.
 * - Webhook callback tự động xác nhận → fire PaymentRecorded event.
 * - Method prepare() sẽ trả về { payment_url, qr_code_url } để Frontend hiển thị.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Khi nâng cấp lên Phase 2, chỉ cần sửa class này duy nhất.
 * Không ảnh hưởng tới các Gateway khác hay ProcessPaymentAction.
 */
class EwalletGateway implements PaymentGatewayInterface
{
    public function validate(RecordPaymentDTO $dto): void
    {
        if (empty($dto->referenceNo)) {
            throw new \DomainException(
                'Thanh toán ví điện tử yêu cầu mã giao dịch (reference_no) từ ứng dụng ví.'
            );
        }
    }

    public function prepare(Order $order, RecordPaymentDTO $dto): ?array
    {
        // Phase 1: Không cần gọi API ngoài.
        // Phase 2 example:
        // $response = $this->momoService->createPaymentRequest($order, $dto->amount);
        // return ['payment_url' => $response->payUrl, 'qr_code_url' => $response->qrCodeUrl];
        return null;
    }

    public function label(): string
    {
        return 'Ví điện tử';
    }
}
