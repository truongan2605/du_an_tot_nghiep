<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckoutAtToDatPhongTable extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->timestamp('checkout_at')->nullable()->after('ngay_tra_phong');
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn('checkout_at');
        });
    }
}
