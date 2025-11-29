<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckoutByToDatPhongTable extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->unsignedBigInteger('checkout_by')->nullable()->after('checkout_at');
            // nếu bạn có bảng users và muốn FK:
            if (Schema::hasTable('users')) {
                $table->foreign('checkout_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasTable('users')) {
                $table->dropForeign([ 'checkout_by' ]);
            }
            $table->dropColumn('checkout_by');
        });
    }
}
