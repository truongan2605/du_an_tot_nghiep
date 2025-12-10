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
            if (!Schema::hasColumn('dat_phong_item', 'number_adult')) {
                $table->unsignedInteger('number_adult')->default(0)->after('number_child')
                    ->comment('Số người lớn trong phòng này');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong_item', 'number_adult')) {
                $table->dropColumn('number_adult');
            }
        });
    }
};
