<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Một đơn hàng có thể có nhiều payment (ví dụ thanh toán split).
     * Thiết kế tách bảng payments ra khỏi orders để:
     * 1. Hỗ trợ thanh toán nhiều lần (split payment) về sau.
     * 2. Dễ audit lịch sử thanh toán.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');

            $table->decimal('amount', 15, 2);

            // Phương thức thanh toán — mở rộng thêm 'ewallet' mà không break
            $table->string('payment_method')->default('cash');

            // Mã tham chiếu (số giao dịch ngân hàng, mã VNPay, v.v.)
            $table->string('reference_no')->nullable();

            $table->timestamp('paid_at')->useCurrent();

            // Audit: nhân viên nào thực hiện thanh toán
            $table->uuid('created_by')->nullable();

            $table->timestamps();

            // ── Foreign Keys ──────────────────────────────────────────────────
            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
