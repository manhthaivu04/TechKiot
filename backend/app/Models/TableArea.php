<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableArea extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'table_areas';

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * Các bàn thuộc khu vực này.
     * Khi xóa area, migration sẽ set area_id = null (onDelete set null).
     */
    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class, 'area_id');
    }
}
