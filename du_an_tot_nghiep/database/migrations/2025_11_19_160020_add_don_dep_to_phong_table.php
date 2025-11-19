<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDonDepToPhongTable extends Migration
{
    public function up()
    {
        Schema::table('phong', function (Blueprint $table) {
            if (!Schema::hasColumn('phong', 'don_dep')) {
                $table->boolean('don_dep')->default(false)->after('trang_thai');
            }
        });


    }

    public function down()
    {
        Schema::table('phong', function (Blueprint $table) {
            if (Schema::hasColumn('phong', 'don_dep')) {
                $table->dropColumn('don_dep');
            }
        });
    }
}
