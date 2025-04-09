<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionToReferensiIsusTable extends Migration
{
    public function up()
    {
        Schema::table('referensi_isus', function (Blueprint $table) {
            $table->text('description')->nullable()->after('url');
        });
    }

    public function down()
    {
        Schema::table('referensi_isus', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
}