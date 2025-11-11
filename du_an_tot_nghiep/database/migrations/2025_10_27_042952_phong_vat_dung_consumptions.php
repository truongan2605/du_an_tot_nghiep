<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('phong_vat_dung_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_id')->nullable()->constrained('dat_phong')->onDelete('set null');
            $table->foreignId('phong_id')->constrained('phong')->onDelete('cascade');
            $table->foreignId('vat_dung_id')->constrained('vat_dungs')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['dat_phong_id', 'phong_id', 'vat_dung_id'], 'idx_phong_vatcon');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('phong_vat_dung_consumptions');
    }
};
