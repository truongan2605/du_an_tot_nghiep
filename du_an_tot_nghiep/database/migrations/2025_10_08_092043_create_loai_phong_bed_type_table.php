<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loai_phong_bed_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loai_phong_id')->constrained('loai_phong')->cascadeOnDelete();
            $table->foreignId('bed_type_id')->constrained('bed_types')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['loai_phong_id', 'bed_type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('loai_phong_bed_type');
    }
};
