<?php

namespace App\Console\Commands;

use App\Contracts\Services\SubscriptionServiceInterface;
use Illuminate\Console\Command;

/**
 * Scheduled command: marks overdue subscriptions as expired.
 *
 * Schedule in routes/console.php:
 *   Schedule::command('subscriptions:expire')->daily();
 */
class ExpireSubscriptionsCommand extends Command
{
    protected $signature   = 'subscriptions:expire';
    protected $description = 'Mark active subscriptions whose end_date has passed as expired.';

    public function handle(SubscriptionServiceInterface $service): int
    {
        $count = $service->expireOverdue();

        $this->info("Expired {$count} subscription(s).");

        return self::SUCCESS;
    }
}
