<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockDocumentItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_document_items';

    /**
     * Bảng này không cần timestamps (created_at, updated_at).
     * Dữ liệu chỉ được tạo 1 lần cùng với chứng từ.
     */
    public $timestamps = false;

    protected $fillable = [
        'stock_document_id',
        'item_id',
        'quantity',
        'unit_cost',
        'total_cost',
    ];

    protected $casts = [
        'quantity'   => 'decimal:3',
        'unit_cost'  => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function stockDocument(): BelongsTo
    {
        return $this->belongsTo(StockDocument::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
