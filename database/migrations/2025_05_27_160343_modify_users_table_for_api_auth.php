<?php
// database/migrations/xxxx_xx_xx_modify_users_table_for_api_auth.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Modifikasi tabel users untuk mendukung autentikasi API:
     * - Menambah kolom api_user_id untuk mapping dengan API
     * - Menambah kolom is_active untuk kontrol akses
     * - Membuat email nullable (tidak wajib saat registrasi awal)
     * - Membuat name nullable (akan diisi dari API saat first login)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Tambah kolom untuk API integration
            $table->string('api_user_id')->nullable()->after('username')
                  ->comment('ID user dari API layanan KSP');
            
            // Kontrol status user
            $table->boolean('is_active')->default(true)->after('role_id')
                  ->comment('Status aktif user untuk kontrol akses');
            
            // Modifikasi kolom existing
            $table->string('email')->nullable()->change()
                  ->comment('Email tidak wajib saat registrasi awal');
            
            $table->string('name')->nullable()->change()
                  ->comment('Nama akan diisi dari API saat first login');
            
            // Index untuk performa
            $table->index('api_user_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['api_user_id']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['api_user_id', 'is_active']);
            
            // Kembalikan ke required
            $table->string('email')->nullable(false)->change();
            $table->string('name')->nullable(false)->change();
        });
    }
};