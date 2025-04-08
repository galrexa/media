<?php
// database/migrations/2023_01_01_000002_create_referensi_isus_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferensiIsusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referensi_isus', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key
            $table->foreignId('isu_id')->constrained('isus')->onDelete('cascade'); // Membuat foreign key yang merujuk ke tabel isus
            $table->string('judul'); // Membuat kolom judul dengan tipe varchar
            $table->text('url'); // Membuat kolom url dengan tipe text
            $table->string('thumbnail')->nullable(); // Membuat kolom thumbnail dengan tipe varchar, boleh null
            $table->timestamps(); // Membuat kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referensi_isus'); // Menghapus tabel referensi_isus jika rollback
    }
}