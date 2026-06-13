<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm cột qr_code_path vào restaurant_payment_methods.
     *
     * Chỉ phương thức "transfer" (chuyển khoản) có ý nghĩa sử dụng cột này.
     * Lưu đường dẫn tương đối trong storage disk "public"
     * (VD: "payment-qr/{restaurant_id}/transfer.webp").
     *
     * URL công khai sẽ được tạo bởi Storage::url($path) trong Resource.
     */
    public function up(): void
    {
        Schema::table('restaurant_payment_methods', function (Blueprint $table) {
            $table->string('qr_code_path')->nullable()->after('display_name');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_payment_methods', function (Blueprint $table) {
            $table->dropColumn('qr_code_path');
        });
    }
};
