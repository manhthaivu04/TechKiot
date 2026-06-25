<?php

namespace App\Models;

use App\Enums\TableStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantTable extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'restaurant_id',
        'area_id',
        'uid',
        'name',
        'capacity',
        'status',
        'qr_token',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status'   => TableStatus::class,
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(TableArea::class, 'area_id');
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }
}
