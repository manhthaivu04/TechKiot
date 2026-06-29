<?php

namespace App\Strategies\Subscription;

use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;

interface SubscriptionStrategyInterface
{
    public function calculatePrice(Package $package, ?PackagePrice $packagePrice = null): float;

    public function calculateEndDate(Carbon $startDate, ?PackagePrice $packagePrice = null): Carbon;
}
