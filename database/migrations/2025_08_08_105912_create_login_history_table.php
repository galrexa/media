<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('login_history', function (Blueprint $table) {
            $table->id();
            
            // User reference
            $table->unsignedSmallInteger('user_id');
            
            // Login information
            $table->enum('login_type', ['web', 'api'])->default('web');
            $table->timestamp('login_at');
            $table->timestamp('logout_at')->nullable();
            
            // Session information - reduced length for index compatibility
            $table->string('session_id', 191)->nullable();
            $table->integer('session_duration_seconds')->nullable();
            
            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            
            // Additional context
            $table->string('login_method', 50)->nullable(); // e.g., 'password', 'remember_token', 'api_key'
            $table->boolean('login_successful')->default(true);
            $table->text('failure_reason')->nullable();
            
            // Role at time of login (for historical tracking)
            $table->string('role_name', 50)->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('login_at');
            $table->index('logout_at');
            $table->index('login_type');
            $table->index('session_id');
            $table->index(['user_id', 'login_at']);
            $table->index(['login_type', 'login_at']);
        });

        // Add foreign key constraint separately to avoid issues
        Schema::table('login_history', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_history', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::dropIfExists('login_history');
    }
};