<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mã chứng từ unique theo nhà hàng (không unique toàn hệ thống).
     */
    public function up(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['restaurant_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_documents', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'code']);
            $table->unique(['code']);
        });
    }
};
