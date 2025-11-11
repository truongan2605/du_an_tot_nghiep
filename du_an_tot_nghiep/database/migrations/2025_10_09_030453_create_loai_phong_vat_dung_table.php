<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loai_phong_vat_dung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loai_phong_id')
                  ->constrained('loai_phong')
                  ->onDelete('cascade');
            $table->foreignId('vat_dung_id')
                  ->constrained('vat_dungs')
                  ->onDelete('cascade');
            $table->integer('so_luong')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loai_phong_vat_dung');
    }
};
