<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSubscription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'restaurant_id',
        'package_id',
        'package_price_id',
        'start_date',
        'end_date',
        'status',
        'activated_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'status'       => SubscriptionStatus::class,
            'activated_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function packagePrice(): BelongsTo
    {
        return $this->belongsTo(PackagePrice::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === SubscriptionStatus::ACTIVE;
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast() || $this->status === SubscriptionStatus::EXPIRED;
    }
}
