<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('phongs')) {
            Schema::table('phongs', function (Blueprint $table) {
                $table->index('loai_phong_id', 'phongs_loai_phong_id_idx');
                $table->index('so_nguoi_toi_da', 'phongs_so_nguoi_toi_da_idx');
                $table->index('gia_theo_dem', 'phongs_gia_theo_dem_idx');
            });
        }

        if (Schema::hasTable('dat_phongs')) {
            Schema::table('dat_phongs', function (Blueprint $table) {
                $table->index('phong_id', 'dat_phongs_phong_id_idx');
                $table->index('trang_thai', 'dat_phongs_trang_thai_idx');
                // Composite index hỗ trợ truy vấn overlap
                $table->index(['phong_id', 'ngay_nhan', 'ngay_tra'], 'dat_phongs_overlap_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('phongs')) {
            Schema::table('phongs', function (Blueprint $table) {
                $table->dropIndex('phongs_loai_phong_id_idx');
                $table->dropIndex('phongs_so_nguoi_toi_da_idx');
                $table->dropIndex('phongs_gia_theo_dem_idx');
            });
        }

        if (Schema::hasTable('dat_phongs')) {
            Schema::table('dat_phongs', function (Blueprint $table) {
                $table->dropIndex('dat_phongs_phong_id_idx');
                $table->dropIndex('dat_phongs_trang_thai_idx');
                $table->dropIndex('dat_phongs_overlap_idx');
            });
        }
    }
};
