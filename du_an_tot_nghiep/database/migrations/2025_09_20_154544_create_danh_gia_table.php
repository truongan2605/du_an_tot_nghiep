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
        Schema::create('danh_gia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_id')->constrained('dat_phong');
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung');
            $table->smallInteger('diem')->unsigned(); 
            $table->text('noi_dung')->nullable();
            $table->json('anh')->nullable(); 
            $table->enum('trang_thai_kiem_duyet', ['cho_kiem_duyet', 'da_dang', 'bi_tu_choi'])->default('cho_kiem_duyet');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('danh_gia');
    }
};
