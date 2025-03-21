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
        Schema::table('isus', function (Blueprint $table) {
            $table->string('main_image')->nullable()->after('judul');
            $table->string('thumbnail_image')->nullable()->after('main_image');
            $table->string('banner_image')->nullable()->after('thumbnail_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('isus', function (Blueprint $table) {
            $table->dropColumn('main_image');
            $table->dropColumn('thumbnail_image');
            $table->dropColumn('banner_image');
        });
    }
};