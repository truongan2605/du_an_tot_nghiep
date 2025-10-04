<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddPhongIdToGiuPhongTable extends Migration
{
    public function up()
    {
        Schema::table('giu_phong', function (Blueprint $table) {
            $table->unsignedBigInteger('phong_id')->after('loai_phong_id')->nullable();
            $table->foreign('phong_id')->references('id')->on('phong')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('giu_phong', function (Blueprint $table) {
            $table->dropForeign(['phong_id']);
            $table->dropColumn('phong_id');
        });
    }
}