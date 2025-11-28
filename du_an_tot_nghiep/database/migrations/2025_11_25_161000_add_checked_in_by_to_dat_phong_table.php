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
        if (!Schema::hasColumn('dat_phong', 'checked_in_by')) {
            Schema::table('dat_phong', function (Blueprint $table) {
                $table->unsignedBigInteger('checked_in_by')->nullable()->after('checked_in_at');
                $table->foreign('checked_in_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('dat_phong', 'checked_in_by')) {
            Schema::table('dat_phong', function (Blueprint $table) {
                $table->dropForeign(['checked_in_by']);
                $table->dropColumn('checked_in_by');
            });
        }
    }
};
