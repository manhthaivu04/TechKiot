<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng inventory_transactions — SỔ KHO bất biến (Immutable Audit Log).
 *
 * Ghi lại MỌI biến động tồn kho: nhập, xuất, điều chỉnh, hao hụt, trả hàng, sử dụng theo công thức.
 * Dữ liệu chỉ INSERT, không UPDATE/DELETE → đảm bảo traceability hoàn toàn.
 *
 * Mỗi dòng = 1 biến động của 1 item tại 1 warehouse:
 *   before_quantity → quantity_change → after_quantity
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('restaurant_id')
                  ->constrained('restaurants')
                  ->cascadeOnDelete();

            $table->foreignUuid('warehouse_id')
                  ->constrained('warehouses')
                  ->cascadeOnDelete();

            $table->foreignUuid('item_id')
                  ->constrained('items')
                  ->cascadeOnDelete();

            // Loại giao dịch kho
            $table->enum('transaction_type', [
                'receipt',      // Nhập kho
                'issue',        // Xuất kho
                'adjustment',   // Điều chỉnh
                'waste',        // Hao hụt
                'return',       // Trả hàng
                'recipe_use',   // Sử dụng theo công thức (trừ khi order)
            ]);

            // Polymorphic-like reference: nguồn gốc giao dịch
            $table->string('reference_type', 50)->nullable();  // VD: 'stock_document', 'order'
            $table->uuid('reference_id')->nullable();           // ID chứng từ/đơn hàng tương ứng

            // Số liệu biến động
            $table->decimal('quantity_change', 12, 3);   // Dương (nhập) hoặc âm (xuất)
            $table->decimal('before_quantity', 12, 3);   // Tồn trước giao dịch
            $table->decimal('after_quantity', 12, 3);    // Tồn sau giao dịch

            $table->text('note')->nullable();

            $table->foreignUuid('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            // Chỉ created_at — immutable log không cần updated_at
            $table->timestamp('created_at')->useCurrent();

            // ── Indexes ────────────────────────────────────────────────────
            $table->index(['warehouse_id', 'item_id', 'created_at'], 'idx_txn_warehouse_item_time');
            $table->index(['reference_type', 'reference_id'], 'idx_txn_reference');
            $table->index(['restaurant_id', 'transaction_type'], 'idx_txn_restaurant_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
