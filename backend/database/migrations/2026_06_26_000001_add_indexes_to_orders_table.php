<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm composite index vào bảng orders để tối ưu hoá các query thống kê.
     *
     * Query analytics luôn filter theo (restaurant_id + status + created_at),
     * composite index này giúp MySQL tránh full-table-scan trên bảng orders lớn.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Index chính cho analytics: lọc theo restaurant + status + khoảng ngày
            $table->index(
                ['restaurant_id', 'status', 'created_at'],
                'orders_restaurant_status_created_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_restaurant_status_created_idx');
        });
    }
};
