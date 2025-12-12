<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHoaDonItemsTable extends Migration
{
    public function up()
    {
        Schema::create('hoa_don_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hoa_don_id')->index();
            $table->string('type'); 
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->unsignedBigInteger('vat_dung_id')->nullable();
            $table->string('name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('amount', 14, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->foreign('hoa_don_id')->references('id')->on('hoa_don')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hoa_don_items');
    }
}

