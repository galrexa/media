<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // Kunci pengaturan yang unik
            $table->text('value'); // Nilai pengaturan
            $table->string('category', 50)->default('general'); // Kategori pengaturan
            $table->timestamps();
        });

        // Masukkan data default untuk modal
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
    }

    /**
     * Seed pengaturan default untuk modal
     */
    private function seedDefaultSettings()
    {
        $settings = [
            [
                'key' => 'modal_title',
                'value' => 'Tentang Media Monitoring',
                'category' => 'modal',
            ],
            [
                'key' => 'modal_content_1',
                'value' => 'Laporan Monitoring Isu Strategis Nasional ini disusun oleh Tim Pengelolaan Media sebagai upaya untuk memahami perspektif media terhadap berbagai kebijakan, isu, dan topik yang berkembang di Indonesia.',
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

        foreach ($settings as $setting) {
            DB::table('settings')->insert($setting);
        }
    }
}