<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng khu vực bàn — nhóm các bàn theo vị trí trong nhà hàng.
     * Cascade xóa khi restaurant bị xóa.
     */
    public function up(): void
    {
        Schema::create('table_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_areas');
    }
};
