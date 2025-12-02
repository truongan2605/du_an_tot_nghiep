<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBlocksCheckinToDatPhong extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->boolean('blocks_checkin')->default(false)->after('is_late_checkout');

        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn(['blocks_checkin', 'blocks_checkin_by', 'blocks_checkin_at', 'blocks_checkin_reason']);
        });
    }
}
