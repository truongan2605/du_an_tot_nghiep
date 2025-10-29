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
        Schema::create('phong_vat_dung', function (Blueprint $table) {
            $table->id();

            // Thêm 2 khóa ngoại chuẩn Laravel
            $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');
            $table->foreignId('vat_dung_id')->constrained('vat_dungs')->onDelete('cascade');

            // Nếu bạn muốn biết thêm trạng thái hoặc số lượng
            // $table->integer('so_luong')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phong_vat_dung');
    }
};
