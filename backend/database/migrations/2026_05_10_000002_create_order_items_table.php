<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lưu từng dòng sản phẩm trong đơn hàng.
     * unit_price = sale_price tại thời điểm đặt (snapshot), tránh bị ảnh hưởng
     * khi người dùng sau này thay đổi giá sản phẩm.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id');
            $table->uuid('item_id');

            $table->unsignedInteger('quantity')->default(1);

            // Snapshot giá tại thời điểm đặt — KHÔNG tham chiếu live price
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2); // = quantity * unit_price

            $table->text('note')->nullable();

            // Trạng thái từng món — dùng cho màn hình bếp (Pro: STAFF_MANAGEMENT)
            $table->string('status')->default('pending');

            $table->timestamps();

            // ── Foreign Keys ──────────────────────────────────────────────────
            $table->foreign('order_id')
                  ->references('id')->on('orders')
                  ->onDelete('cascade');

            $table->foreign('item_id')
                  ->references('id')->on('items')
                  ->onDelete('restrict'); // Không cho xóa item đang có trong đơn
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
