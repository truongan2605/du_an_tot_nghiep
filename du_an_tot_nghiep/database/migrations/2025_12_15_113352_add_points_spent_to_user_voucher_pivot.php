<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('user_voucher', function (Blueprint $table) {
            $table->unsignedInteger('points_spent')->nullable()->after('claimed_at');
        });
    }

    public function down()
    {
        Schema::table('user_voucher', function (Blueprint $table) {
            $table->dropColumn('points_spent');
        });
    }
};
