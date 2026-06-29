<?php

namespace App\Strategies\Subscription;

use App\Models\Package;
use App\Models\PackagePrice;
use Carbon\Carbon;

class SubscriptionStrategyContext
{
    private SubscriptionStrategyInterface $strategy;

    public function __construct(Package $package, ?PackagePrice $packagePrice = null)
    {
        if (! $packagePrice) {
            $this->strategy = new LegacySubscriptionStrategy();
        } elseif ($packagePrice->duration_days == 30) {
            $this->strategy = new MonthlySubscriptionStrategy();
        } elseif ($packagePrice->duration_days == 90) {
            $this->strategy = new QuarterlySubscriptionStrategy();
        } elseif ($packagePrice->duration_days == 365) {
            $this->strategy = new AnnualSubscriptionStrategy();
        } else {
            $this->strategy = new CustomDurationSubscriptionStrategy();
        }
    }

    public function getCalculatedPrice(Package $package, ?PackagePrice $packagePrice = null): float
    {
        return $this->strategy->calculatePrice($package, $packagePrice);
    }

    public function getCalculatedEndDate(Carbon $startDate, ?PackagePrice $packagePrice = null): Carbon
    {
        return $this->strategy->calculateEndDate($startDate, $packagePrice);
    }
}
