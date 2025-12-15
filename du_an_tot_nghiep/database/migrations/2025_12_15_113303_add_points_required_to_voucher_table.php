<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('voucher', function (Blueprint $table) {
            $table->unsignedInteger('points_required')->nullable()->after('active');
        });
    }

    public function down()
    {
        Schema::table('voucher', function (Blueprint $table) {
            $table->dropColumn('points_required');
        });
    }
};
