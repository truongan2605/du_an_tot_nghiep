<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEarlyCheckoutToDatPhong extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->boolean('is_checkout_early')->default(false)->after('checkout_at');
            $table->decimal('early_checkout_refund_amount', 15, 2)->nullable()->after('is_checkout_early');
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn('early_checkout_refund_amount');
            $table->dropColumn('is_checkout_early');
        });
    }
}
