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
        Schema::create('phong_da_dat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_item_id')->constrained('dat_phong_item');
            $table->foreignId('phong_id')->constrained('phong');
            $table->enum('trang_thai', ['da_dat', 'dang_su_dung', 'hoan_thanh', 'da_huy'])->default('da_dat');
            $table->timestamp('checkin_datetime')->nullable();
            $table->timestamp('checkout_datetime')->nullable();
            $table->timestamp('thuc_te_nhan_phong_luc')->nullable();
            $table->timestamp('thuc_te_tra_phong_luc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phong_da_dat');
    }
};
