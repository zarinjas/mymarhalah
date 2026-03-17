<?php

namespace App\Providers;

use App\Events\UserOrganizationTransitioned;
use App\Listeners\LogTransitionAndNotify;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * We register the event→listener mapping here rather than relying solely on
     * Laravel's auto-discovery because it keeps the binding explicit and visible
     * to developers who open this file first when debugging the transition engine.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // ─── Transition Engine Events ─────────────────────────────────────────
        Event::listen(
            UserOrganizationTransitioned::class,
            LogTransitionAndNotify::class,
        );
    }
}
