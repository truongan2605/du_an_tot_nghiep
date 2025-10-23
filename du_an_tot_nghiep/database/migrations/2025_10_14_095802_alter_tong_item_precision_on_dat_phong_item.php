<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->decimal('tong_item', 14, 2)->nullable()->change();
            $table->decimal('gia_tren_dem', 14, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->decimal('tong_item', 8, 2)->nullable()->change();
            $table->decimal('gia_tren_dem', 8, 2)->nullable()->change();
        });
    }
};
