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
   Schema::create('reviews', function (Blueprint $table) {
    $table->id(); // id tự tăng của bảng reviews

    $table->foreignId('dat_phong_id')->constrained('dat_phongs')->onDelete('cascade');
    $table->foreignId('nguoi_dung_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('phong_id')->constrained('phongs')->onDelete('cascade');

    $table->unsignedTinyInteger('rating')->nullable(); // 1–5 sao
    $table->text('comment')->nullable();
    $table->boolean('status')->default(1); // 1: hiển thị, 0: ẩn
    $table->timestamps();
});
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
