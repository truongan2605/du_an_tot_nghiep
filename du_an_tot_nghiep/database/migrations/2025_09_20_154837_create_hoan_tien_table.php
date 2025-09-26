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
        Schema::create('hoan_tien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('giao_dich_id')->constrained('giao_dich');
            $table->decimal('so_tien', 12, 2);
            $table->string('provider_ref')->nullable();
            $table->enum('trang_thai', ['dang_cho', 'thanh_cong', 'that_bai', 'da_hoan'])->default('dang_cho');
            $table->text('ly_do')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoan_tien');
    }
};
