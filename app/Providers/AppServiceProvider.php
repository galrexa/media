<?php
// app/Providers/AppServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\ViewComposers\SidebarComposer;
//use App\Http\ViewComposers\NotificationComposer;
use Illuminate\Support\Facades\View;
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
        // Register KSP API Service
        $this->app->singleton(KspApiService::class, function ($app) {
            return new KspApiService();
        });
    
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
        View::composer('partials.sidebar', SidebarComposer::class);
        //View::composer('layouts.admin', NotificationComposer::class);
        Log::info('AppServiceProvider: ViewComposers registered');

        // PDF Configuration
        if (class_exists('\Barryvdh\DomPDF\ServiceProvider')) {
            // Set default options untuk PDF
            Pdf::setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => false,
            ]);
        }
    }
}
