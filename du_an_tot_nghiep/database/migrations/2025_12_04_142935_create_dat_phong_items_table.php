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
       Schema::create('dat_phong_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('dat_phong_id')->constrained('dat_phong')->onDelete('cascade');
    $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');

    $table->integer('gia');
    $table->integer('so_luong')->default(1);

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dat_phong_items');
    }
};
