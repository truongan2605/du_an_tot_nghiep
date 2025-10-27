<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBilledToConsumptionsAndIncidents extends Migration
{
    public function up()
    {
        Schema::table('phong_vat_dung_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('phong_vat_dung_consumptions', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('note');
            }
        });

        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_dung_incidents', 'dat_phong_id')) {
                $table->unsignedBigInteger('dat_phong_id')->nullable()->after('phong_id')->index();
            }
            if (!Schema::hasColumn('vat_dung_incidents', 'billed_at')) {
                $table->timestamp('billed_at')->nullable()->after('fee');
            }
        });


    }

    public function down()
    {
        Schema::table('phong_vat_dung_consumptions', function (Blueprint $table) {
            $table->dropColumn('billed_at');
        });
        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            $table->dropColumn(['dat_phong_id', 'billed_at']);
        });
    }
}
