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
        // AI Analysis Results Table
        Schema::create('ai_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->json('urls'); // Array of URLs analyzed
            $table->json('extracted_content')->nullable(); // Raw extracted content
            $table->text('ai_resume')->nullable();
            $table->json('ai_judul_suggestions')->nullable(); // Array of title suggestions
            $table->text('ai_narasi_positif')->nullable();
            $table->text('ai_narasi_negatif')->nullable();
            $table->enum('ai_tone_suggestion', ['positif', 'negatif', 'netral'])->nullable();
            $table->enum('ai_skala_suggestion', ['rendah', 'sedang', 'tinggi'])->nullable();
            $table->json('confidence_scores')->nullable(); // Per-field confidence scores
            $table->enum('processing_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('processing_time')->nullable(); // in seconds
            $table->text('error_message')->nullable();
            $table->string('ai_provider')->default('groq'); // groq, openai, claude, etc
            $table->string('ai_model')->nullable(); // llama-3.1-8b-instant, etc
            $table->timestamps();
            
            // Indexes
            $table->index('session_id');
            $table->index('user_id');
            $table->index('processing_status');
            $table->index('created_at');
            
            // Foreign key
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // AI Usage Logs Table
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('analysis_id')->nullable();
            $table->integer('urls_count');
            $table->enum('processing_status', ['pending', 'success', 'failed']);
            $table->string('ai_provider'); // groq, openai, claude
            $table->string('ai_model')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->decimal('cost_estimation', 10, 6)->nullable(); // in USD
            $table->json('error_details')->nullable();
            $table->integer('response_time')->nullable(); // in milliseconds
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes
            $table->index('user_id');
            $table->index('analysis_id');
            $table->index('ai_provider');
            $table->index('created_at');
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('analysis_id')->references('id')->on('ai_analysis_results')->onDelete('set null');
        });

        // AI Configurations Table (for dynamic settings)
        Schema::create('ai_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('category')->default('general');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('category');
        });

        // Insert default configurations
        DB::table('ai_configurations')->insert([
            [
                'key' => 'groq_enabled',
                'value' => 'true',
                'category' => 'provider',
                'description' => 'Enable Groq API integration',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'default_model',
                'value' => 'llama-3.1-8b-instant',
                'category' => 'model',
                'description' => 'Default Llama model for analysis',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'max_urls_per_request',
                'value' => '5',
                'category' => 'limits',
                'description' => 'Maximum URLs per analysis request',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'key' => 'timeout_seconds',
                'value' => '60',
                'category' => 'limits',
                'description' => 'API timeout in seconds',
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_configurations');
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_analysis_results');
    }
};