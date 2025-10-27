<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vat_dung_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_vat_dung_instance_id')->nullable()->constrained('phong_vat_dung_instances')->onDelete('set null');
            $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');
            $table->foreignId('vat_dung_id')->constrained('vat_dungs')->onDelete('cascade');
            $table->enum('type', ['damage','loss','other']);
            $table->text('description')->nullable();
            $table->decimal('fee', 12, 2)->default(0);
            $table->foreignId('reported_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_dung_incidents');
    }
};
