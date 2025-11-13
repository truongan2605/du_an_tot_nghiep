<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('phong_vat_dung', function (Blueprint $table) {
            if (!Schema::hasColumn('phong_vat_dung','so_luong')) {
                $table->integer('so_luong')->default(0)->after('vat_dung_id');
            }
            if (!Schema::hasColumn('phong_vat_dung','da_tieu_thu')) {
                $table->integer('da_tieu_thu')->default(0)->after('so_luong');
            }
            if (!Schema::hasColumn('phong_vat_dung','gia_override')) {
                $table->decimal('gia_override', 12, 2)->nullable()->after('da_tieu_thu');
            }
            if (!Schema::hasColumn('phong_vat_dung','tracked_instances')) {
                $table->boolean('tracked_instances')->default(false)->after('gia_override');
            }
            $table->unique(['phong_id', 'vat_dung_id'], 'phong_vatdung_unique');
        });
    }

    public function down(): void
    {
        Schema::table('phong_vat_dung', function (Blueprint $table) {
            $table->dropUnique('phong_vatdung_unique');
            $table->dropColumn(['so_luong','da_tieu_thu','gia_override','tracked_instances']);
        });
    }
};

