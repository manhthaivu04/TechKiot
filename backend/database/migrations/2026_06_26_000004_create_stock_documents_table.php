<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng chứng từ kho — Phiếu nhập/xuất/điều chỉnh/hao hụt/trả hàng.
     * Lifecycle: draft → confirmed / cancelled.
     */
    public function up(): void
    {
        Schema::create('stock_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('restaurant_id');
            $table->uuid('warehouse_id');
            $table->enum('document_type', ['receipt', 'issue', 'adjustment', 'waste', 'return']);
            $table->string('code', 50)->unique();
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');
            $table->text('note')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('restaurant_id')
                  ->references('id')->on('restaurants')
                  ->onDelete('cascade');

            $table->foreign('warehouse_id')
                  ->references('id')->on('warehouses')
                  ->onDelete('cascade');

            $table->foreign('created_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');

            // Tối ưu query: lấy chứng từ theo restaurant + status
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_documents');
    }
};
