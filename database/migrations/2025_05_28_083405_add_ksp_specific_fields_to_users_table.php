<?php
// database/migrations/2024_01_01_000003_add_ksp_specific_fields_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan field khusus untuk data dari API KSP
     * Berdasarkan struktur response API yang sebenarnya
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Field yang sudah ada di migration sebelumnya (pastikan ada)
            if (!Schema::hasColumn('users', 'api_user_id')) {
                $table->string('api_user_id')->nullable()->after('username')
                      ->comment('ID user dari API KSP (field: id_user)');
            }
            
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('role_id')
                      ->comment('Status aktif user');
            }
            
            if (!Schema::hasColumn('users', 'last_api_login')) {
                $table->timestamp('last_api_login')->nullable()->after('updated_at')
                      ->comment('Waktu terakhir login via API KSP');
            }
            
            // Field baru khusus untuk data KSP
            if (!Schema::hasColumn('users', 'position')) {
                $table->string('position')->nullable()->after('name')
                      ->comment('Jabatan dari API KSP (field: jabatan)');
            }
            
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable()->after('position')
                      ->comment('Unit kerja dari API KSP (field: satuankerja)');
            }
            
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id')->nullable()->after('api_user_id')
                      ->comment('ID pegawai dari API KSP (field: id_pegawai)');
            }
            
            if (!Schema::hasColumn('users', 'ksp_group')) {
                $table->string('ksp_group')->nullable()->after('department')
                      ->comment('Group name dari API KSP (field: gname)');
            }
            
            if (!Schema::hasColumn('users', 'age')) {
                $table->integer('age')->nullable()->after('ksp_group')
                      ->comment('Umur dari API KSP (field: umur)');
            }
            
            if (!Schema::hasColumn('users', 'birth_date')) {
                $table->date('birth_date')->nullable()->after('age')
                      ->comment('Tanggal lahir dari API KSP (field: tanggal_lahir)');
            }
            
            if (!Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo')->nullable()->after('birth_date')
                      ->comment('Info foto profil dari API KSP (field: foto)');
            }
            
            if (!Schema::hasColumn('users', 'ksp_data')) {
                $table->json('ksp_data')->nullable()->after('profile_photo')
                      ->comment('Data tambahan dari API KSP (privileges, roles, dll)');
            }
        });
        
        // Pastikan email dan name nullable
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email')) {
                $table->string('email')->nullable()->change()
                      ->comment('Email dari API KSP atau manual');
            }
            
            if (Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->change()
                      ->comment('Nama lengkap dari API KSP (field: namalengkap)');
            }
        });
        
        // Tambahkan index untuk performance
        Schema::table('users', function (Blueprint $table) {
            $indexes = [
                'employee_id' => 'users_employee_id_index',
                'position' => 'users_position_index', 
                'department' => 'users_department_index',
                'last_api_login' => 'users_last_api_login_index',
            ];
            
            foreach ($indexes as $column => $indexName) {
                if (!$this->hasIndex('users', $indexName)) {
                    $table->index($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Drop indexes first
            $indexes = ['employee_id', 'position', 'department', 'last_api_login'];
            foreach ($indexes as $column) {
                try {
                    $table->dropIndex(['users_' . $column . '_index']);
                } catch (\Exception $e) {
                    // Ignore if index doesn't exist
                }
            }
            
            // Drop columns
            $columnsToRemove = [
                'employee_id',
                'position',
                'department', 
                'ksp_group',
                'age',
                'birth_date',
                'profile_photo',
                'ksp_data',
                'last_api_login',
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
    
    /**
     * Check if an index exists on a table
     */
    private function hasIndex($table, $index)
    {
        try {
            $indexes = Schema::getConnection()
                            ->getDoctrineSchemaManager()
                            ->listTableIndexes($table);
            
            return array_key_exists($index, $indexes);
        } catch (\Exception $e) {
            return false;
        }
    }
};