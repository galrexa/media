<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Cek apakah tabel 'settings' sudah dibuat
        if (Schema::hasTable('settings')) {
            // Ambil pengaturan modal untuk disertakan di semua view
            $modalSettings = Setting::where('category', 'modal')->get();

            // Buat array untuk menyimpan pengaturan dengan key sebagai index
            $settings = [];
            foreach ($modalSettings as $setting) {
                $settings[$setting->key] = $setting->value;
            }

            // Share data ke semua view
            View::share('appSettings', $settings);

            // Helper function untuk mendapatkan modal content
            View::composer('*', function ($view) {
                $modalTitle = Setting::getValue('modal_title', 'Tentang Media Monitoring');
                $modalContents = Setting::getByCategory('modal')
                    ->filter(function($item) {
                        return str_contains($item->key, 'modal_content');
                    })
                    ->sortBy('key');
                $buttonText = Setting::getValue('modal_button_text', 'Mengerti');

                $view->with('modalTitle', $modalTitle);
                $view->with('modalContents', $modalContents);
                $view->with('modalButtonText', $buttonText);
            });
        }
    }
}
