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
            // Check if column exists before adding
            if (!Schema::hasColumn('dat_phong_item', 'number_child')) {
                $table->unsignedInteger('number_child')->default(0)->after('so_nguoi_o')
                    ->comment('Số trẻ em (>= 7 tuổi) trong phòng này');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong_item', 'number_child')) {
                $table->dropColumn('number_child');
            }
        });
    }
};
