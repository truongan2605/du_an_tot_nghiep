<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('vat_dung_incidents', 'dat_phong_id')) {
                $table->foreignId('dat_phong_id')->nullable()->after('phong_id')->constrained('dat_phong')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('vat_dung_incidents', function (Blueprint $table) {
            if (Schema::hasColumn('vat_dung_incidents', 'dat_phong_id')) {
                $table->dropForeign(['dat_phong_id']);
                $table->dropColumn('dat_phong_id');
            }
        });
    }
};
