<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Package extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'code',
        'name',
        'description',
        'price',
        'duration_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price'        => 'decimal:2',
            'duration_days'=> 'integer',
            'is_active'    => 'boolean',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'package_features');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(RestaurantSubscription::class);
    }
}
