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
            if (!Schema::hasColumn('dat_phong', 'is_early_checkin')) {
                $table->boolean('is_early_checkin')->default(false)->after('blocks_checkin');
            }
            if (!Schema::hasColumn('dat_phong', 'early_checkin_fee_amount')) {
                $table->decimal('early_checkin_fee_amount', 12, 0)->nullable()->default(0)->after('is_early_checkin');
            }
            if (!Schema::hasColumn('dat_phong', 'is_late_checkin')) {
                $table->boolean('is_late_checkin')->default(false)->after('early_checkin_fee_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong', 'is_late_checkin')) {
                $table->dropColumn('is_late_checkin');
            }
            if (Schema::hasColumn('dat_phong', 'early_checkin_fee_amount')) {
                $table->dropColumn('early_checkin_fee_amount');
            }
            if (Schema::hasColumn('dat_phong', 'is_early_checkin')) {
                $table->dropColumn('is_early_checkin');
            }
        });
    }
};
