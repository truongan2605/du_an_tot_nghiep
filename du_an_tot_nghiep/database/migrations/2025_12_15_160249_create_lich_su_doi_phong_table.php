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
      Schema::create('lich_su_doi_phong', function (Blueprint $table) {
    $table->id();

    $table->unsignedBigInteger('dat_phong_id');
    $table->unsignedBigInteger('dat_phong_item_id');

    $table->unsignedBigInteger('phong_cu_id');
    $table->unsignedBigInteger('phong_moi_id');

    $table->decimal('gia_cu', 15, 2);
    $table->decimal('gia_moi', 15, 2);
    $table->integer('so_dem');

    $table->enum('loai', ['nang_cap', 'ha_cap'])->nullable();
    $table->string('nguoi_thuc_hien')->nullable(); // admin / khách

    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lich_su_doi_phong');
    }
};
