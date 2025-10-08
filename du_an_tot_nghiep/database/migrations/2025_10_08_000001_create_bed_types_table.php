<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBedTypesTable extends Migration
{
    public function up()
    {
        Schema::create('bed_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); 
            $table->string('slug')->nullable()->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('capacity')->default(1); 
            $table->decimal('price', 12, 2)->default(0); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bed_types');
    }
}
