<?php

namespace App\Models;

use App\Enums\ItemAvailabilityStatus;
use App\Enums\ItemType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
