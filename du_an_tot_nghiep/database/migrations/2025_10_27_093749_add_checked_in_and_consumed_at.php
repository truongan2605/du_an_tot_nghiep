<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCheckedInAndConsumedAt extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (!Schema::hasColumn('dat_phong', 'checked_in_at')) {
                $table->timestamp('checked_in_at')->nullable()->after('ngay_nhan_phong')->index();
            }
        });

        Schema::table('phong_vat_dung_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('phong_vat_dung_consumptions', 'consumed_at')) {
                $table->timestamp('consumed_at')->nullable()->after('quantity')->index();
            }
            if (!Schema::hasColumn('phong_vat_dung_consumptions', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('note')->index();
            }
        });

        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_dung_incidents', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('fee')->index();
            }
            if (!Schema::hasColumn('vat_dung_incidents', 'dat_phong_id')) {
                $table->unsignedBigInteger('dat_phong_id')->nullable()->after('phong_id')->index();
            }
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong', 'checked_in_at')) $table->dropColumn('checked_in_at');
        });
        Schema::table('phong_vat_dung_consumptions', function (Blueprint $table) {
            if (Schema::hasColumn('phong_vat_dung_consumptions', 'consumed_at')) $table->dropColumn('consumed_at');
            if (Schema::hasColumn('phong_vat_dung_consumptions', 'billed_at')) $table->dropColumn('billed_at');
        });
        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('vat_dung_incidents', 'billed_at')) $table->dropColumn('billed_at');
            if (Schema::hasColumn('vat_dung_incidents', 'dat_phong_id')) $table->dropColumn('dat_phong_id');
        });
    }
}
