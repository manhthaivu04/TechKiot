<?php

namespace App\Strategies\Subscription;

use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;

class MonthlySubscriptionStrategy implements SubscriptionStrategyInterface
{
    public function calculatePrice(Package $package, ?PackagePrice $packagePrice = null): float
    {
        return (float) ($packagePrice ? $packagePrice->price : $package->price);
    }

    public function calculateEndDate(Carbon $startDate, ?PackagePrice $packagePrice = null): Carbon
    {
        return $startDate->copy()->addMonth();
    }
}
