<?php
// app/Providers/AlertServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Helpers\AlertHelper;

class AlertServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Bind AlertHelper ke container
        $this->app->singleton('alert', function ($app) {
            return new AlertHelper();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publikasikan aset jika dijalankan di console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../resources/js/sweetalert.js' => resource_path('js/sweetalert.js'),
            ], 'alert');
        }

        // Tambahkan direktif Blade untuk memudahkan penggunaan SweetAlert
        Blade::directive('sweetalertScripts', function () {
            return '<?php echo view("components.sweet-alert-scripts")->render(); ?>';
        });

        // Tambahkan direktif Blade untuk menampilkan alert
        Blade::directive('sweetalertInit', function () {
            return '<?php echo view("components.sweet-alert-init")->render(); ?>';
        });
    }
}