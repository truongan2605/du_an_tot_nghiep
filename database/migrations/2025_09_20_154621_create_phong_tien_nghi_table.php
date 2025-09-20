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
        Schema::create('phong_tien_nghi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong');
            $table->foreignId('tien_nghi_id')->constrained('tien_nghi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phong_tien_nghi');
    }
};
