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
            $table->unsignedInteger('so_nguoi_o')->default(0)->after('so_luong')
                ->comment('Số người thực tế ở trong phòng này (để tính phụ thu chính xác)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->dropColumn('so_nguoi_o');
        });
    }
};
