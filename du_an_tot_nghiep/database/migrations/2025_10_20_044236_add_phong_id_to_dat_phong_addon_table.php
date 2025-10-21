<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhongIdToDatPhongAddonTable extends Migration
{
    public function up()
    {
        Schema::table('dat_phong_addon', function (Blueprint $table) {
            $table->unsignedBigInteger('phong_id')->nullable()->after('dat_phong_id');
            $table->foreign('phong_id')->references('id')->on('phong')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('dat_phong_addon', function (Blueprint $table) {
            $table->dropForeign(['phong_id']);
            $table->dropColumn('phong_id');
        });
    }
}
