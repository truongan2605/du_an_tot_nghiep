<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('danh_gia', function (Blueprint $table) {
            $table->dropColumn(['anh', 'trang_thai_kiem_duyet']);
        });
    }

    public function down(): void
    {
        Schema::table('danh_gia', function (Blueprint $table) {
            $table->json('anh')->nullable();
            $table->enum('trang_thai_kiem_duyet', ['cho_kiem_duyet', 'da_dang', 'bi_tu_choi'])->default('cho_kiem_duyet');
        });
    }
};
