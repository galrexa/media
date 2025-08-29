<?php

// app/Providers/RouteServiceProvider.php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        // TAMBAHKAN RATE LIMITERS INI
        RateLimiter::for('ai-analyze', function (Request $request) {
            return Limit::perUser(10)->perMinute(); // 10 requests per minute per user
        });

        RateLimiter::for('ai-status', function (Request $request) {
            return Limit::perUser(60)->perMinute(); // 60 requests per minute for status checking
        });

        RateLimiter::for('ai-results', function (Request $request) {
            return Limit::perUser(30)->perMinute(); // 30 requests per minute for results
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}