<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Laravel 12 double-fire fix ───────────────────────────────────────
        // Tắt auto-discovery ở BASE class scope. Phải gọi ở đây (trước khi
        // base EventServiceProvider được boot bởi ApplicationBuilder).
        EventServiceProvider::disableEventDiscovery();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
