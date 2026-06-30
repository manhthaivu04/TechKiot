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
        'qr_code_url',
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

    public function tableAreas(): HasMany
    {
        return $this->hasMany(TableArea::class);
    }

    public function tables(): HasMany
    {
        return $this->hasMany(RestaurantTable::class);
    }

    public function paymentMethods(): HasMany
    {
        return $this->hasMany(RestaurantPaymentMethod::class);
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Kiểm tra một phương thức thanh toán có đang được bật không.
     * Trả về true nếu chưa có config (mặc định cho phép).
     */
    public function isPaymentMethodActive(\App\Enums\PaymentMethod $method): bool
    {
        $config = $this->paymentMethods()
            ->where('payment_method', $method->value)
            ->first();

        // Chưa có config → coi như đang bật (safe default)
        return $config === null || $config->is_active;
    }
}
