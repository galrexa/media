<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIsuKategoriTable extends Migration
{
    public function up()
    {
        Schema::create('isu_kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('isu_id')->constrained('isus')->onDelete('cascade');
            $table->foreignId('kategori_id')->constrained('kategoris')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('isu_kategori');
    }
}