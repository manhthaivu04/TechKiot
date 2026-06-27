<?php

namespace App\Strategies\Subscription;

use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;

class LegacySubscriptionStrategy implements SubscriptionStrategyInterface
{
    public function calculatePrice(Package $package, ?PackagePrice $packagePrice = null): float
    {
        return (float) $package->price;
    }

    public function calculateEndDate(Carbon $startDate, ?PackagePrice $packagePrice = null): Carbon
    {
        return $startDate->copy()->addDays($package->duration_days);
    }
}
