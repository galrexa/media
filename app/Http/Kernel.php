<?php
// app/Http/Kernel.php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's route middleware.
     *
     * @var array<string, class-string|string>
     */
    protected $middlewareAliases = [
        // middleware lainnya...
        'role' => \App\Http\Middleware\RoleMiddleware::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            (new \App\Http\Controllers\TrendingController())->fetchTrends24(true);
        })->hourly();
    }
}