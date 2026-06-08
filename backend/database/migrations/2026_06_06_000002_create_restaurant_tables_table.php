<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng bàn ăn của nhà hàng.
     *
     * Ràng buộc:
     * - uid unique trong phạm vi từng restaurant (composite unique).
     * - qr_token unique toàn hệ thống (dùng cho QR order tương lai).
     * - Khi xóa area → area_id = null (set null).
     * - Khi xóa restaurant → cascade xóa bàn.
     */
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('area_id')->nullable();
            $table->string('uid', 50);
            $table->string('name', 100);
            $table->integer('capacity')->default(4);
            $table->string('status', 20)->default('available');
            $table->string('qr_token', 120)->unique();
            $table->timestamps();

            // Composite unique: uid chỉ cần unique trong phạm vi nhà hàng
            $table->unique(['restaurant_id', 'uid']);

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');

            $table->foreign('area_id')
                  ->references('id')->on('table_areas')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_tables');
    }
};
