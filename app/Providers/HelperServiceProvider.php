<?php
// app/Providers/HelperServiceProvider.php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Http\ViewComposers\SidebarComposer;

class HelperServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Require helper files
        require_once app_path('Helpers/DateHelper.php');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register ViewComposer untuk sidebar
        View::composer('partials.sidebar', SidebarComposer::class);

        // Debugging message
        \Log::info('HelperServiceProvider: SidebarComposer registered');
    }
}
