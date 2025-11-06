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
    Schema::table('dat_phong', function (Blueprint $table) {
        $table->string('voucher_code')->nullable()->after('tong_tien');
    });
}

public function down(): void
{
    Schema::table('dat_phong', function (Blueprint $table) {
        $table->dropColumn(['voucher_code']);
    });
}
};
