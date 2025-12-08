<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('danh_gia_space', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dat_phong_id')->constrained('dat_phong')->onDelete('cascade');
            $table->unsignedTinyInteger('rating')->nullable(); // 1–5 sao (1 lần duy nhất)
            $table->text('noi_dung')->nullable(); // bình luận
            $table->foreignId('parent_id')->nullable()->constrained('danh_gia_space')->onDelete('cascade'); // bình luận con
            $table->boolean('is_new')->default(true); // đánh giá mới
            $table->boolean('status')->default(true); // 1: hiển thị, 0: ẩn
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('danh_gia_space');
    }
};
