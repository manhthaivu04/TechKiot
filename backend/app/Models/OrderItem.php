<?php

namespace App\Models;

use App\Enums\OrderItemStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'item_id',
        'quantity',
        'unit_price',
        'line_total',
        'note',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status'     => OrderItemStatus::class,
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Lấy thông tin Item gốc (name, image...) để hiển thị.
     * unit_price trong order_items là snapshot — không thay đổi dù Item.sale_price đổi.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
