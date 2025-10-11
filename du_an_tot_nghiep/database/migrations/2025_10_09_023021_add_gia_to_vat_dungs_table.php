<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
    Schema::table('vat_dungs', function (Blueprint $table) {
        $table->decimal('gia', 10, 2)->default(0)->after('ten'); 
        // 10,2 = 10 số, 2 số thập phân (ví dụ: 1000000.50)
    });
}

public function down()
{
    Schema::table('vat_dungs', function (Blueprint $table) {
        $table->dropColumn('gia');
    });
}
};
