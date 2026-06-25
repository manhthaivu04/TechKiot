<?php

namespace App\Services\Payments;

use App\Contracts\Payments\PaymentGatewayInterface;
use App\Enums\PaymentMethod;
use App\Services\Payments\Gateways\CardGateway;
use App\Services\Payments\Gateways\CashGateway;
use App\Services\Payments\Gateways\EwalletGateway;
use App\Services\Payments\Gateways\TransferGateway;

/**
 * PaymentGatewayFactory — Khởi tạo đúng Strategy dựa vào PaymentMethod enum.
 *
 * ── Factory Pattern ────────────────────────────────────────────────────────────
 * Tập trung việc "chọn" Strategy tại một nơi duy nhất.
 * Caller (ProcessPaymentAction) không cần biết class cụ thể nào được dùng.
 *
 * ── OCP ───────────────────────────────────────────────────────────────────────
 * Thêm cổng mới (VNPay, Stripe)? → Thêm 1 case vào match() + 1 class Gateway mới.
 * Không sửa bất kỳ class nào khác.
 */
class PaymentGatewayFactory
{
    public static function make(PaymentMethod $method): PaymentGatewayInterface
    {
        return match ($method) {
            PaymentMethod::Cash     => new CashGateway(),
            PaymentMethod::Card     => new CardGateway(),
            PaymentMethod::Transfer => new TransferGateway(),
            PaymentMethod::Ewallet  => new EwalletGateway(),
        };
    }
}
