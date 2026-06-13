<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\PaymentMethod;

return new class extends Migration
{
    /**
     * Bảng cấu hình phương thức thanh toán theo từng nhà hàng.
     *
     * Mỗi restaurant có 4 bản ghi mặc định (1 per PaymentMethod enum).
     * OWNER/MANAGER có thể bật/tắt từng phương thức.
     *
     * ── Tại sao dùng bảng riêng thay vì JSON column? ────────────────────────
     * - Dễ query: WHERE is_active = 1 AND restaurant_id = ?
     * - Dễ audit: updated_at tự động ghi nhận lần tắt/bật cuối
     * - Dễ mở rộng: thêm config mới (VD: daily_limit, fee_percent) mà không break
     */
    public function up(): void
    {
        Schema::create('restaurant_payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');

            // Tương ứng với PaymentMethod enum (cash | card | transfer | ewallet)
            $table->string('payment_method');

            // 1 = bật, 0 = tắt — tên rõ ràng hơn boolean để dễ hiểu
            $table->boolean('is_active')->default(true);

            // Nhãn hiển thị tùy chỉnh (tùy chọn — nếu quán muốn đổi tên)
            $table->string('display_name')->nullable();

            $table->timestamps();

            // ── Constraints ───────────────────────────────────────────────────
            // Unique: mỗi restaurant chỉ có 1 bản ghi per phương thức
            $table->unique(['restaurant_id', 'payment_method']);

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_payment_methods');
    }
};
