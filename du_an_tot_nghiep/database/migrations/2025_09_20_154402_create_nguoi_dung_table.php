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
        Schema::create('nguoi_dung', function (Blueprint $table) {
            $table->id();
            $table->string('ten');
            $table->string('email')->unique();
            $table->string('so_dien_thoai')->nullable();
            $table->string('mat_khau_hash');
            $table->enum('vai_tro', ['khach_hang', 'nhan_vien', 'admin'])->default('khach_hang');
            $table->string('phong_ban')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nguoi_dung');
    }
};