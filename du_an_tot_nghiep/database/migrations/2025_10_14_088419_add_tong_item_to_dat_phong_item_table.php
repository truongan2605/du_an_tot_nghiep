<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->decimal('tong_item')->after('so_dem');
        });
    }

    public function down()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->dropColumn('tong_item');
        });
    }
};
