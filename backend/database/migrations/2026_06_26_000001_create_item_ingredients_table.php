<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng pivot `item_ingredients` — Bill of Materials (BOM).
 *
 * Quan hệ Many-to-Many self-referencing trên bảng `items`:
 *   - product_id    → Items có item_type = MENU_ITEM  (Món ăn)
 *   - ingredient_id → Items có item_type = INGREDIENT (Nguyên liệu)
 *
 * Không dùng bảng `recipes` riêng lẻ — pivot này đủ để lưu
 * "Món A cần X gram Nguyên liệu B" một cách gọn gàng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_ingredients', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('ingredient_id');
            $table->decimal('quantity', 12, 3);
            $table->timestamps();

            // ── Primary key ghép ─────────────────────────────────────────────
            $table->primary(['product_id', 'ingredient_id']);

            // ── Foreign keys ─────────────────────────────────────────────────
            $table->foreign('product_id')
                  ->references('id')->on('items')
                  ->onDelete('cascade');

            $table->foreign('ingredient_id')
                  ->references('id')->on('items')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_ingredients');
    }
};
