<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddActiveToLoaiPhong extends Migration
{
    public function up()
    {
        Schema::table('loai_phong', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('so_luong_thuc_te');
        });
    }

    public function down()
    {
        Schema::table('loai_phong', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }
}
