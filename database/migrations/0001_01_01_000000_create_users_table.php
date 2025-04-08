<?php
// database/migrations/2014_10_12_000000_create_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id(); // Membuat kolom id sebagai primary key auto-increment
            $table->string('name'); // Membuat kolom name dengan tipe varchar
            $table->string('email')->unique(); // Membuat kolom email unik
            $table->timestamp('email_verified_at')->nullable(); // Kolom verifikasi email (opsional)
            $table->string('password'); // Kolom password
            $table->enum('role', ['admin', 'editor', 'viewer'])->default('viewer'); // Membuat kolom role dengan tipe enum
            $table->rememberToken(); // Token untuk fitur "remember me"
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
        Schema::dropIfExists('users'); // Menghapus tabel users jika rollback
    }
}