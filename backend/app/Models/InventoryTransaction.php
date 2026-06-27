<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InventoryTransaction — Model cho SỔ KHO bất biến.
 *
 * Mỗi record là 1 dòng ghi sổ:
 *   before_quantity → (+/-) quantity_change → after_quantity
 *
 * Bất biến (Immutable): Chỉ INSERT, không UPDATE/DELETE.
 * - Tắt updated_at (chỉ dùng created_at).
 * - Không dùng SoftDeletes.
 */
class InventoryTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'inventory_transactions';

    /**
     * Chỉ sử dụng created_at — log bất biến không cần updated_at.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'restaurant_id',
        'warehouse_id',
        'item_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'quantity_change',
        'before_quantity',
        'after_quantity',
        'note',
        'created_by',
    ];

    protected $casts = [
        'transaction_type' => TransactionType::class,
        'quantity_change'  => 'decimal:3',
        'before_quantity'  => 'decimal:3',
        'after_quantity'   => 'decimal:3',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
