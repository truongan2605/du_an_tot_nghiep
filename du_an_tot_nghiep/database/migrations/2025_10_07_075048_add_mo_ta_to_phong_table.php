<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoTaToPhongTable extends Migration
{
    public function up()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->text('mo_ta')->nullable()->after('name');
        });
    }

    public function down()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->dropColumn('mo_ta');
        });
    }
}
