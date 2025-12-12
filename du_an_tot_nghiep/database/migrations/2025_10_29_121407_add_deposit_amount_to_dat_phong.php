<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->decimal('deposit_amount', 15, 2)->nullable()->default(0.00)->after('tong_tien'); // NEW: Decimal cho tiền tệ, nullable cho legacy
        });
    }

    public function down(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });
    }
};