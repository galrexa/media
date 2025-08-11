<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists first
        if (!Schema::hasTable('user_analytics')) {
            // Create the table if it doesn't exist
            Schema::create('user_analytics', function (Blueprint $table) {
                $table->id();
                $table->unsignedSmallInteger('user_id')->nullable();
                $table->string('role_name', 50)->nullable();
                $table->unsignedTinyInteger('role_id')->nullable();
                $table->string('user_department', 191)->nullable();
                $table->string('user_position', 191)->nullable();
                $table->string('session_id', 191)->nullable();
                $table->unsignedBigInteger('login_history_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('page_url')->nullable();
                $table->string('page_name', 100)->nullable();
                $table->string('page_title', 191)->nullable();
                $table->json('page_params')->nullable();
                $table->string('action_type', 50)->nullable();
                $table->json('action_details')->nullable();
                $table->timestamp('visited_at')->nullable();
                $table->timestamp('left_at')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->text('referrer')->nullable();
                $table->boolean('is_bounce')->default(false);
                $table->timestamps();
                
                // Add indexes
                $table->index('user_id');
                $table->index('role_name');
                $table->index('role_id');
                $table->index('action_type');
                $table->index('login_history_id');
                $table->index('visited_at');
                $table->index('session_id');
                $table->index(['role_name', 'visited_at']);
            });
        } else {
            // Update existing table
            Schema::table('user_analytics', function (Blueprint $table) {
                // Check and add columns if they don't exist
                if (!Schema::hasColumn('user_analytics', 'role_name')) {
                    $table->string('role_name', 50)->nullable()->after('user_id');
                }
                
                if (!Schema::hasColumn('user_analytics', 'role_id')) {
                    $table->unsignedTinyInteger('role_id')->nullable()->after('role_name');
                }
                
                if (!Schema::hasColumn('user_analytics', 'login_history_id')) {
                    $table->unsignedBigInteger('login_history_id')->nullable()->after('session_id');
                }
                
                if (!Schema::hasColumn('user_analytics', 'action_type')) {
                    $table->string('action_type', 50)->nullable()->after('page_name');
                }
                
                if (!Schema::hasColumn('user_analytics', 'action_details')) {
                    $table->json('action_details')->nullable()->after('action_type');
                }
                
                if (!Schema::hasColumn('user_analytics', 'user_department')) {
                    $table->string('user_department', 191)->nullable()->after('role_name');
                }
                
                if (!Schema::hasColumn('user_analytics', 'user_position')) {
                    $table->string('user_position', 191)->nullable()->after('user_department');
                }
            });
            
            // Add indexes separately to avoid duplicate index errors
            Schema::table('user_analytics', function (Blueprint $table) {
                // Check if index exists before adding
                $indexName = 'user_analytics_role_name_index';
                if (!$this->indexExists('user_analytics', $indexName)) {
                    $table->index('role_name');
                }
                
                $indexName = 'user_analytics_role_id_index';
                if (!$this->indexExists('user_analytics', $indexName)) {
                    $table->index('role_id');
                }
                
                $indexName = 'user_analytics_action_type_index';
                if (!$this->indexExists('user_analytics', $indexName)) {
                    $table->index('action_type');
                }
                
                $indexName = 'user_analytics_login_history_id_index';
                if (!$this->indexExists('user_analytics', $indexName)) {
                    $table->index('login_history_id');
                }
                
                // Composite index
                $indexName = 'user_analytics_role_name_visited_at_index';
                if (!$this->indexExists('user_analytics', $indexName)) {
                    $table->index(['role_name', 'visited_at']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('user_analytics')) {
            Schema::table('user_analytics', function (Blueprint $table) {
                // Drop indexes if they exist
                $indexesToDrop = [
                    'user_analytics_role_name_visited_at_index',
                    'user_analytics_login_history_id_index',
                    'user_analytics_action_type_index',
                    'user_analytics_role_id_index',
                    'user_analytics_role_name_index'
                ];
                
                foreach ($indexesToDrop as $indexName) {
                    if ($this->indexExists('user_analytics', $indexName)) {
                        $table->dropIndex($indexName);
                    }
                }
                
                // Drop columns if they exist
                $columnsToDrop = [
                    'role_name',
                    'role_id',
                    'login_history_id',
                    'action_type',
                    'action_details',
                    'user_department',
                    'user_position'
                ];
                
                foreach ($columnsToDrop as $column) {
                    if (Schema::hasColumn('user_analytics', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
    
    /**
     * Check if index exists
     */
    private function indexExists($table, $indexName)
    {
        $indexes = DB::select("SHOW INDEXES FROM {$table}");
        foreach ($indexes as $index) {
            if ($index->Key_name === $indexName) {
                return true;
            }
        }
        return false;
    }
};