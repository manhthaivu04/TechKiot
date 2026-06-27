<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng tồn kho hiện tại (snapshot).
     * Mỗi nguyên liệu × mỗi kho = 1 dòng duy nhất (UNIQUE warehouse_id + item_id).
     * restaurant_id denormalized để tối ưu query tenant isolation.
     */
    public function up(): void
    {
        Schema::create('inventory', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('warehouse_id');
            $table->uuid('item_id');
            $table->decimal('quantity', 12, 3)->default(0);
            $table->timestamps();

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('cascade');

            $table->foreign('item_id')
                  ->references('id')->on('items')
                  ->onDelete('cascade');

            // Mỗi item chỉ xuất hiện 1 lần trong 1 kho
            $table->unique(['warehouse_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory');
    }
};
