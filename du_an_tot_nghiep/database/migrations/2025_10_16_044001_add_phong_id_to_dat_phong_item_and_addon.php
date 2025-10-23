<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPhongIdToDatPhongItemAndAddon extends Migration
{
    public function up()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            if (!Schema::hasColumn('dat_phong_item', 'phong_id')) {
                $table->unsignedBigInteger('phong_id')->nullable()->after('dat_phong_id');
                $table->index('phong_id');
                $table->foreign('phong_id')->references('id')->on('phong')->onDelete('set null');
            }
        });

    }

    public function down()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong_item', 'phong_id')) {
                $table->dropForeign(['phong_id']);
                $table->dropIndex(['phong_id']);
                $table->dropColumn('phong_id');
            }
        });

    }
}
