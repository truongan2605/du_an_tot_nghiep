<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('phong', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phong')->unique();
            $table->foreignId('loai_phong_id')->constrained('loai_phong');
            $table->foreignId('tang_id')->constrained('tang');
            $table->integer('suc_chua');
            $table->integer('so_giuong');
            $table->decimal('gia_mac_dinh', 12, 2);
            $table->enum('trang_thai', ['trong','da_dat', 'dang_o', 'bao_tri', 'khong_su_dung'])->default('trong');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phong');
    }
};
