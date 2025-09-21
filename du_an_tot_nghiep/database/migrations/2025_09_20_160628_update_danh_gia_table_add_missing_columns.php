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
        Schema::table('danh_gia', function (Blueprint $table) {
            $table->dropColumn(['diem_so', 'is_public']);
            $table->smallInteger('diem')->unsigned()->after('nguoi_dung_id');
            $table->json('anh')->nullable()->after('noi_dung');
            $table->enum('trang_thai_kiem_duyet', ['cho_kiem_duyet', 'da_dang', 'bi_tu_choi'])->default('cho_kiem_duyet')->after('anh');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('danh_gia', function (Blueprint $table) {
            $table->dropColumn(['diem', 'anh', 'trang_thai_kiem_duyet']);
            $table->integer('diem_so')->unsigned();
            $table->boolean('is_public')->default(true);
        });
    }
};
