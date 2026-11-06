<?php

namespace App\Providers;

use App\Services\Midtrans;
use App\Services\Tenancy;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(Midtrans::class, fn () => Midtrans::fromConfig());
        $this->app->scoped(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Overwritten per request by ResolveTenant; null outside HTTP (mail, console).
        View::share('tenant', null);
    }
}
