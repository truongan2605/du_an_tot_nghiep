<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('phong_tien_nghi_override', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phong_id')->index();
            $table->unsignedBigInteger('tien_nghi_id')->index();
            $table->unsignedBigInteger('applies_to_dat_phong_id')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->foreign('phong_id')->references('id')->on('phong')->onDelete('cascade');
            $table->foreign('tien_nghi_id')->references('id')->on('tien_nghi')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('phong_tien_nghi_override');
    }
};
