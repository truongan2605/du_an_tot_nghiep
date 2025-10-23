<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGiaCuoiCungToPhongTable extends Migration
{
    public function up()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->decimal('gia_cuoi_cung', 12, 2)->default(0)->after('gia_mac_dinh');
        });
    }

    public function down()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->dropColumn('gia_cuoi_cung');
        });
    }
}
