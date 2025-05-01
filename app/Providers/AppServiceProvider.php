<?php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\Paginator;

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
        
        Schema::defaultStringLength(191);
        // Register role middleware
        Route::aliasMiddleware('role', RoleMiddleware::class);

        // Di AppServiceProvider.php atau file bootstrap tertentu
        ini_set('allow_url_fopen', 'On');
        // Kode lainnya...

        Paginator::useBootstrap();

        Carbon::setLocale('id');
    }
}