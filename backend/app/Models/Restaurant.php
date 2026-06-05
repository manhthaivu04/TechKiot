<?php

namespace App\Models;

use App\Enums\RestaurantStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Restaurant extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'public_order_token',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => RestaurantStatus::class,
        ];
    }

    // ─── Relationships ───────────────────────────────────────────────────────

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(RestaurantSubscription::class);
    }

    /**
     * The currently active subscription (if any).
     */
    public function activeSubscription(): HasOne
    {
        return $this->hasOne(RestaurantSubscription::class)
            ->where('status', 'active')
            ->latestOfMany('created_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Return the feature codes the restaurant currently has access to.
     */
    public function activeFeatureCodes(): array
    {
        $subscription = $this->activeSubscription;

        if (! $subscription) {
            return [];
        }

        return $subscription->package
            ->features()
            ->where('is_active', true)
            ->pluck('code')
            ->toArray();
    }

    public function hasFeature(string $featureCode): bool
    {
        return in_array($featureCode, $this->activeFeatureCodes(), true);
    }

    public function isAccessible(): bool
    {
        return $this->status->isAccessible();
    }
}
