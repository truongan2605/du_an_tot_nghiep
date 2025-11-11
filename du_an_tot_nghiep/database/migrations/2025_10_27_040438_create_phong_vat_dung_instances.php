<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phong_vat_dung_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');
            $table->foreignId('vat_dung_id')->constrained('vat_dungs')->onDelete('cascade');
            $table->string('serial')->nullable();
            $table->enum('status', ['present','damaged','missing'])->default('present');
            $table->text('note')->nullable();
            $table->decimal('damage_fee', 12, 2)->nullable();
            $table->decimal('loss_fee', 12, 2)->nullable();
            $table->timestamps();
            $table->index(['phong_id','vat_dung_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phong_vat_dung_instances');
    }
};
