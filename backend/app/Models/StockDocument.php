<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockDocument extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_documents';

    protected $fillable = [
        'restaurant_id',
        'warehouse_id',
        'document_type',
        'code',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'document_type' => DocumentType::class,
        'status'        => DocumentStatus::class,
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stockDocumentItems(): HasMany
    {
        return $this->hasMany(StockDocumentItem::class);
    }

    // ─── State Pattern ───────────────────────────────────────────────────────

    /**
     * Lấy đối tượng State hiện tại theo State Pattern.
     * Tương tự Order::state() — delegate hành vi cho State object.
     */
    public function state(): \App\States\StockDocument\DocumentState
    {
        return match ($this->status) {
            DocumentStatus::DRAFT     => new \App\States\StockDocument\DraftState($this),
            DocumentStatus::CONFIRMED => new \App\States\StockDocument\ConfirmedState($this),
            DocumentStatus::CANCELLED => new \App\States\StockDocument\CancelledState($this),
        };
    }
}
