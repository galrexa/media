<?php
// database/migrations/2023_01_01_000003_create_images_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key
            $table->integer('tanggal'); // Membuat kolom tanggal dengan tipe integer sesuai diagram
            $table->string('media_1')->nullable(); // Membuat kolom media_1 dengan tipe varchar
            $table->string('media_2')->nullable(); // Membuat kolom media_2 dengan tipe varchar
            $table->string('media_3')->nullable(); // Membuat kolom media_3 dengan tipe varchar
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
        Schema::dropIfExists('images'); // Menghapus tabel images jika rollback
    }
}