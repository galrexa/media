<?php
// database/migrations/2023_01_01_000005_create_trendings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrendingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trendings', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key
            $table->foreignId('media_sosial_id')->constrained('media_sosials')->onDelete('cascade'); // Membuat foreign key ke tabel media_sosials
            $table->dateTime('tanggal'); // Membuat kolom tanggal dengan tipe datetime
            $table->string('judul'); // Membuat kolom judul dengan tipe varchar
            $table->string('url'); // Membuat kolom url dengan tipe varchar
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
        Schema::dropIfExists('trendings'); // Menghapus tabel trendings jika rollback
    }
}