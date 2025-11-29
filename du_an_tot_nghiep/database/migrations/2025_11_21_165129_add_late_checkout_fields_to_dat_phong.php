<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLateCheckoutFieldsToDatPhong extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (!Schema::hasColumn('dat_phong', 'is_late_checkout')) {
                $table->boolean('is_late_checkout')->default(false)->after('is_checkout_early');
            }
            if (!Schema::hasColumn('dat_phong', 'late_checkout_fee_amount')) {
                $table->decimal('late_checkout_fee_amount', 12, 0)->nullable()->default(0)->after('early_checkout_refund_amount');
            }
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong', 'late_checkout_fee_amount')) {
                $table->dropColumn('late_checkout_fee_amount');
            }
            if (Schema::hasColumn('dat_phong', 'is_late_checkout')) {
                $table->dropColumn('is_late_checkout');
            }
        });
    }
}
