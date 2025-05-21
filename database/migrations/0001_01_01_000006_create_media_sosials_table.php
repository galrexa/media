<?php
// database/migrations/2023_01_01_000004_create_media_sosials_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediaSosialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('media_sosials', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key
            $table->string('nama'); // Membuat kolom nama dengan tipe varchar
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
        Schema::dropIfExists('media_sosials'); // Menghapus tabel media_sosials jika rollback
    }
}