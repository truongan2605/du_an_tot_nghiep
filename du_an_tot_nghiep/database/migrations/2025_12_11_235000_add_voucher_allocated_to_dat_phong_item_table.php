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
        Schema::table('dat_phong_item', function (Blueprint $table) {
            // Số tiền voucher được phân bổ cho phòng này
            $table->decimal('voucher_allocated', 12, 2)->default(0)->after('tong_item')
                ->comment('Số tiền voucher được giảm cho phòng này');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->dropColumn('voucher_allocated');
        });
    }
};
