<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;


return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'loai_phong_id')) {
            try {
                Schema::table('dat_phong_item', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `dat_phong_item` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NULL");
            }
        }


        if (Schema::hasTable('dat_phong_addon') && Schema::hasColumn('dat_phong_addon', 'loai_phong_id')) {
            try {
                Schema::table('dat_phong_addon', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `dat_phong_addon` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NULL");
            }
        }


        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'loai_phong_id')) {
            try {
                Schema::table('giu_phong', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable()->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `giu_phong` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NULL");
            }
        }
    }


    public function down()
    {
        if (Schema::hasTable('dat_phong_item') && Schema::hasColumn('dat_phong_item', 'loai_phong_id')) {
            try {
                Schema::table('dat_phong_item', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `dat_phong_item` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NOT NULL");
            }
        }


        if (Schema::hasTable('dat_phong_addon') && Schema::hasColumn('dat_phong_addon', 'loai_phong_id')) {
            try {
                Schema::table('dat_phong_addon', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `dat_phong_addon` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NOT NULL");
            }
        }


        if (Schema::hasTable('giu_phong') && Schema::hasColumn('giu_phong', 'loai_phong_id')) {
            try {
                Schema::table('giu_phong', function (Blueprint $table) {
                    $table->unsignedBigInteger('loai_phong_id')->nullable(false)->change();
                });
            } catch (\Throwable $e) {
                DB::statement("ALTER TABLE `giu_phong` MODIFY COLUMN `loai_phong_id` BIGINT UNSIGNED NOT NULL");
            }
        }
    }
};
