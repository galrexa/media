<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Hapus semua pengaturan yang sudah ada (jika ada)
        Setting::truncate();
        
        // Pengaturan default untuk modal
        $settings = [
            [
                'key' => 'modal_title',
                'value' => 'Tentang Media Monitoring',
                'category' => 'modal',
            ],
            [
                'key' => 'modal_content_1',
                'value' => 'Laporan Monitoring Isu Strategis Nasional ini disusun oleh Tim Pengelolaan Media sebagai upaya untuk memahami perspektif media terhadap berbagai kebijakan, isu, dan topik yang berkembang di Indonesia pada',
                'category' => 'modal',
            ],
            [
                'key' => 'modal_content_2',
                'value' => 'Isu-isu strategis yang disajikan dalam laporan ini bersumber dari penelitian kualitatif yang dikaji Tim Pengelolaan Media KSP melalui pemberitaan di media cetak dan media online.',
                'category' => 'modal',
            ],
            [
                'key' => 'modal_content_3',
                'value' => 'Analisis yang dilakukan bertujuan untuk memberikan wawasan bagi insan Kantor Staf Presiden (KSP) dalam mencermati dinamika pemberitaan di media massa. Selain itu, laporan ini diharapkan dapat menjadi referensi dalam diskusi serta landasan dalam menindaklanjuti isu-isu yang berkembang.',
                'category' => 'modal',
            ],
            [
                'key' => 'modal_button_text',
                'value' => 'Mengerti',
                'category' => 'modal',
            ],
        ];

        // Insert semua pengaturan default
        foreach ($settings as $setting) {
            Setting::create($setting);
        }
        
        // Tambahkan beberapa pengaturan general jika diperlukan
        Setting::create([
            'key' => 'site_title',
            'value' => 'Media Monitoring KSP',
            'category' => 'general',
        ]);
        
        Setting::create([
            'key' => 'copyright_text',
            'value' => 'Media Monitoring © Kantor Staf Presiden',
            'category' => 'general',
        ]);
    }
}