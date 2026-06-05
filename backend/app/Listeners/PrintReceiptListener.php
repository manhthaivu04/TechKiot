<?php

namespace App\Listeners;

use App\Events\PaymentRecorded;
use Illuminate\Support\Facades\Log;

/**
 * PrintReceiptListener — Xử lý in/ghi nhận hóa đơn sau khi thanh toán.
 *
 * ── Observer Pattern ─────────────────────────────────────────────────────────
 * Listener này được kích hoạt sau khi sự kiện PaymentRecorded được fire.
 * Hiện tại đang log thông tin ra file để debug.
 *
 * ── Lộ trình mở rộng ─────────────────────────────────────────────────────────
 * Bước 1 (Hiện tại): Log thông tin thanh toán ra storage/logs/laravel.log
 * Bước 2 (PRO):      Tạo file PDF hóa đơn bằng barryvdh/laravel-dompdf
 * Bước 3 (PREMIUM):  Gửi PDF qua Email/Zalo OA tới khách hàng
 */
class PrintReceiptListener
{
    /**
     * Handle the event.
     */
    public function handle(PaymentRecorded $event): void
    {
        $order   = $event->order;
        $payment = $event->payment;

        Log::channel('daily')->info('💳 [RECEIPT] Thanh toán được ghi nhận', [
            'order_code'     => $order->order_code,
            'order_id'       => $order->id,
            'payment_id'     => $payment->id,
            'amount'         => number_format((float) $payment->amount, 0, ',', '.') . ' đ',
            'method'         => $payment->payment_method->value,
            'reference_no'   => $payment->reference_no,
            'paid_at'        => $payment->paid_at?->format('d/m/Y H:i:s'),
            'order_status'   => $order->status->value,
            'is_paid_full'   => $order->isPaidInFull() ? 'CÓ' : 'CHƯA ĐỦ',
            'final_amount'   => number_format((float) $order->final_amount, 0, ',', '.') . ' đ',
            'total_paid'     => number_format((float) $order->payments()->sum('amount'), 0, ',', '.') . ' đ',
        ]);

        // ── TODO [PRO]: Sinh PDF hóa đơn ────────────────────────────────────
        // $pdf = PDF::loadView('receipts.order', compact('order'));
        // $pdf->save(storage_path("receipts/order-{$order->order_code}.pdf"));

        // ── TODO [PREMIUM]: Gửi SMS/Email cho khách ─────────────────────────
        // if ($order->customer_phone) {
        //     SendReceiptSmsJob::dispatch($order, $payment);
        // }
    }
}
