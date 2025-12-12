<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    Schema::table('dat_phong', function (Blueprint $table) {
        $table->boolean('can_xac_nhan')->default(false)->after('trang_thai');
    });
}

public function down()
{
    Schema::table('dat_phong', function (Blueprint $table) {
        $table->dropColumn('can_xac_nhan');
    });
}
};
