<?php

namespace App\Models;

use App\Enums\ItemAvailabilityStatus;
use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'items';

    protected $fillable = [
        'restaurant_id',
        'item_group_id',
        'name',
        'item_type',
        'unit',
        'image_url',
        'description',
        'cost_price',
        'sale_price',
        'is_active',
        'availability_status',
    ];

    protected $casts = [
        'item_type' => ItemType::class,
        'availability_status' => ItemAvailabilityStatus::class,
        'cost_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function itemGroup(): BelongsTo
    {
        return $this->belongsTo(ItemGroup::class, 'item_group_id');
    }

    // ── BOM (Bill of Materials) ──────────────────────────────────────────────

    /**
     * Nguyên liệu cấu thành món ăn này (MENU_ITEM → INGREDIENTs).
     *
     * Dùng khi: Load công thức chế biến của 1 món.
     * VD: $phoBoItem->ingredients => [thịt bò, bánh phở, nước dùng, ...]
     */
    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'item_ingredients',  // pivot table
            'product_id',        // FK trỏ tới item hiện tại (Món ăn)
            'ingredient_id',     // FK trỏ tới item liên quan (Nguyên liệu)
        )->withPivot('quantity')
         ->withTimestamps();
    }

    /**
     * Các món ăn đang sử dụng nguyên liệu này (INGREDIENT → MENU_ITEMs).
     *
     * Dùng khi: Kiểm tra nguyên liệu X đang được dùng trong bao nhiêu món.
     * VD: $thitBoItem->usedAsIngredientIn => [Phở bò, Bún bò, ...]
     */
    public function usedAsIngredientIn(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'item_ingredients',  // pivot table
            'ingredient_id',     // FK trỏ tới item hiện tại (Nguyên liệu)
            'product_id',        // FK trỏ tới item liên quan (Món ăn)
        )->withPivot('quantity')
         ->withTimestamps();
    }

    // ─── Inventory ───────────────────────────────────────────────────────────

    /**
     * Tồn kho của nguyên liệu này tại các kho chứa.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }
}
