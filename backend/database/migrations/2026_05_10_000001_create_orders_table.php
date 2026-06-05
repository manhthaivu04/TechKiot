<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thiết kế bảng orders hỗ trợ đa kênh (cashier, qr_static, qr_table...).
     * table_id nullable để Basic không cần table, Pro mới dùng.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('table_id')->nullable(); // Pro: TABLE_MANAGEMENT

            // Mã đơn hàng duy nhất, readable (e.g. KT-20240510-0001)
            $table->string('order_code')->unique();

            // service_type: phân loại đơn tại bàn hay mang đi
            $table->string('service_type')->default('takeaway');
            // source_channel: kênh tạo đơn — mở rộng cho qr_table (Pro) về sau
            $table->string('source_channel')->default('cashier');

            // Vòng đời đơn hàng — kiểm soát bằng OrderStatus Enum + State Pattern
            $table->string('status')->default('open');

            // Thông tin khách hàng (tùy chọn, dành cho kênh QR)
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_reference')->nullable(); // mã bàn / số thứ tự QR

            $table->unsignedInteger('guest_count')->default(1);

            // Tài chính
            $table->decimal('subtotal_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('final_amount', 15, 2)->default(0);

            $table->text('note')->nullable();

            // Audit: ai tạo đơn (null nếu khách tự đặt qua QR)
            $table->uuid('created_by')->nullable();

            $table->timestamps();

            // ── Foreign Keys ──────────────────────────────────────────────────
            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');

            // table_id FK không cascade vì bảng tables chưa tồn tại (Pro module)
            // Sẽ thêm FK khi migrate Pro module: TABLE_MANAGEMENT
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
