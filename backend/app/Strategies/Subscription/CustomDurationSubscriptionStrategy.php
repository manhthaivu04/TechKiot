<?php

namespace App\Strategies\Subscription;

use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;

class CustomDurationSubscriptionStrategy implements SubscriptionStrategyInterface
{
    public function calculatePrice(Package $package, ?PackagePrice $packagePrice = null): float
    {
        return (float) ($packagePrice ? $packagePrice->price : $package->price);
    }

    public function calculateEndDate(Carbon $startDate, Package $package, ?PackagePrice $packagePrice = null): Carbon
    {
        $durationDays = $packagePrice ? $packagePrice->duration_days : $package->duration_days;
        return $startDate->copy()->addDays($durationDays);
    }
}
