<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->json('snapshot_meta')->nullable()->after('snapshot_total');
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn('snapshot_meta');
        });
    }
};
