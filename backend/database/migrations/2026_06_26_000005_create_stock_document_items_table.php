<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng chi tiết dòng của chứng từ kho.
     * Cascade xóa khi chứng từ bị xóa.
     */
    public function up(): void
    {
        Schema::create('stock_document_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_document_id');
            $table->uuid('item_id');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 14, 2)->default(0);

            $table->foreign('stock_document_id')
                  ->references('id')->on('stock_documents')
                  ->onDelete('cascade');

            $table->foreign('item_id')
                  ->references('id')->on('items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_document_items');
    }
};
