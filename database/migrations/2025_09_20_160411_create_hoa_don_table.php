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
        Schema::create('hoa_don', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_id')->constrained('dat_phong');
            $table->string('so_hoa_don')->unique();
            $table->decimal('tong_thuc_thu', 12, 2);
            $table->string('don_vi', 10)->default('VND');
            $table->enum('trang_thai', ['tao', 'da_xuat', 'da_thanh_toan', 'da_huy'])->default('tao');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoa_don');
    }
};
