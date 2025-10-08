<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhongBedTable extends Migration
{
    public function up()
    {
        Schema::create('phong_bed', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong')->cascadeOnDelete();
            $table->foreignId('bed_type_id')->constrained('bed_types')->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(0); 
            $table->timestamps();

            $table->unique(['phong_id', 'bed_type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('phong_bed');
    }
}
