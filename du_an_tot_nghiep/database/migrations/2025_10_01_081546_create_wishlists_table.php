<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phong_id')->constrained('phong')->cascadeOnDelete(); 
            $table->timestamps();

            $table->unique(['user_id', 'phong_id']); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
