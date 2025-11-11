<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('phong_vat_dung_instances', function (Blueprint $table) {
            if (! Schema::hasColumn('phong_vat_dung_instances', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('note');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('phong_vat_dung_instances', 'quantity')) {
                $table->integer('quantity')->default(1)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('phong_vat_dung_instances', function (Blueprint $table) {
            if (Schema::hasColumn('phong_vat_dung_instances', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
            if (Schema::hasColumn('phong_vat_dung_instances', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
