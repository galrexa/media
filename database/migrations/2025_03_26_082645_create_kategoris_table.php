<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKategorisTable extends Migration
{
    public function up()
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id(); // Kolom ID otomatis
            $table->string('nama')->unique(); // Nama kategori, unik
            $table->text('deskripsi')->nullable(); // Deskripsi kategori (opsional)
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('kategoris');
    }
}