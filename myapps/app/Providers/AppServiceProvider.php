<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use App\Auth\StafUserProvider;
use App\Models\Staf;

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
     */
    public function boot(): void
    {
        // Register custom Staf user provider
        Auth::provider('staf', function ($app, array $config) {
            return new StafUserProvider();
        });
    }
}
