<?php
// database/migrations/0001_01_01_000002_create_isus_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIsusTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('isus', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key
            $table->dateTime('tanggal'); // Membuat kolom tanggal dengan tipe datetime
            $table->boolean('isu_strategis')->default(false); // Kolom boolean untuk menentukan apakah isu strategis
            $table->string('kategori'); // Membuat kolom kategori dengan tipe varchar
            $table->string('skala'); // Membuat kolom skala dengan tipe varchar
            $table->string('judul'); // Membuat kolom judul dengan tipe varchar
            $table->text('rangkuman'); // Membuat kolom rangkuman dengan tipe text
            $table->text('narasi_positif'); // Membuat kolom narasi_positif dengan tipe text
            $table->text('narasi_negatif'); // Membuat kolom narasi_negatif dengan tipe text
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
        Schema::dropIfExists('isus'); // Menghapus tabel isus jika rollback
    }
}