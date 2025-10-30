<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCheckedInAtToDatPhong extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('ngay_tra_phong');
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn('checked_in_at');
        });
    }
}