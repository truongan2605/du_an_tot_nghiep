<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // danh_gia: đổi FK nguoi_dung_id -> users.id
        if (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia', 'nguoi_dung_id')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->dropForeign(['nguoi_dung_id']);
                $table->foreign('nguoi_dung_id')->references('id')->on('users');
            });
        }

        // dat_phong: đổi FK created_by và nguoi_dung_id -> users.id
        if (Schema::hasTable('dat_phong')) {
            Schema::table('dat_phong', function (Blueprint $table) {
                if (Schema::hasColumn('dat_phong', 'created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('users');
                }
                if (Schema::hasColumn('dat_phong', 'nguoi_dung_id')) {
                    $table->dropForeign(['nguoi_dung_id']);
                    $table->foreign('nguoi_dung_id')->references('id')->on('users');
                }
            });
        }

        // giu_phong: released_by -> users.id
        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'released_by')) {
            Schema::table('giu_phong', function (Blueprint $table) {
                $table->dropForeign(['released_by']);
                $table->foreign('released_by')->references('id')->on('users');
            });
        }

        // thong_bao: nguoi_nhan_id -> users.id
        if (Schema::hasTable('thong_bao') && Schema::hasColumn('thong_bao', 'nguoi_nhan_id')) {
            Schema::table('thong_bao', function (Blueprint $table) {
                $table->dropForeign(['nguoi_nhan_id']);
                $table->foreign('nguoi_nhan_id')->references('id')->on('users');
            });
        }

        // voucher_usage: nguoi_dung_id -> users.id
        if (Schema::hasTable('voucher_usage') && Schema::hasColumn('voucher_usage', 'nguoi_dung_id')) {
            Schema::table('voucher_usage', function (Blueprint $table) {
                $table->dropForeign(['nguoi_dung_id']);
                $table->foreign('nguoi_dung_id')->references('id')->on('users');
            });
        }

    }

    public function down(): void
    {
        if (Schema::hasTable('danh_gia') && Schema::hasColumn('danh_gia','nguoi_dung_id')) {
            Schema::table('danh_gia', function (Blueprint $table) {
                $table->dropForeign(['nguoi_dung_id']);
                $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dung');
            });
        }

        if (Schema::hasTable('dat_phong')) {
            Schema::table('dat_phong', function (Blueprint $table) {
                if (Schema::hasColumn('dat_phong','created_by')) {
                    $table->dropForeign(['created_by']);
                    $table->foreign('created_by')->references('id')->on('nguoi_dung');
                }
                if (Schema::hasColumn('dat_phong','nguoi_dung_id')) {
                    $table->dropForeign(['nguoi_dung_id']);
                    $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dung');
                }
            });
        }

        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong','released_by')) {
            Schema::table('giu_phong', function (Blueprint $table) {
                $table->dropForeign(['released_by']);
                $table->foreign('released_by')->references('id')->on('nguoi_dung');
            });
        }

        if (Schema::hasTable('thong_bao') && Schema::hasColumn('thong_bao','nguoi_nhan_id')) {
            Schema::table('thong_bao', function (Blueprint $table) {
                $table->dropForeign(['nguoi_nhan_id']);
                $table->foreign('nguoi_nhan_id')->references('id')->on('nguoi_dung');
            });
        }

        if (Schema::hasTable('voucher_usage') && Schema::hasColumn('voucher_usage','nguoi_dung_id')) {
            Schema::table('voucher_usage', function (Blueprint $table) {
                $table->dropForeign(['nguoi_dung_id']);
                $table->foreign('nguoi_dung_id')->references('id')->on('nguoi_dung');
            });
        }
    }
};
