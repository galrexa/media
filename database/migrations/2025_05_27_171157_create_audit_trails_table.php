<?php
// database/migrations/2024_01_01_000001_create_audit_trails_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Membuat tabel audit_trails untuk logging aktivitas user
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()
                  ->comment('ID user yang melakukan aktivitas');
            $table->string('username')->nullable()
                  ->comment('Username user yang melakukan aktivitas');
            $table->string('activity')
                  ->comment('Jenis aktivitas yang dilakukan');
            $table->text('details')->nullable()
                  ->comment('Detail aktivitas');
            $table->ipAddress('ip_address')->nullable()
                  ->comment('IP address user');
            $table->text('user_agent')->nullable()
                  ->comment('Browser/User agent information');
            $table->timestamps();
            
            // Indexes untuk performance
            $table->index('user_id');
            $table->index('username');
            $table->index('activity');
            $table->index('created_at');
            
            // Foreign key constraint (optional, bisa di-comment jika tidak diperlukan)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};