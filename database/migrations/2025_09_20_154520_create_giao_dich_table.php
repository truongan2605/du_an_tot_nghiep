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
        Schema::create('giao_dich', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_id')->constrained('dat_phong');
            $table->string('nha_cung_cap');
            $table->string('provider_txn_ref')->nullable();
            $table->decimal('so_tien', 12, 2);
            $table->string('don_vi', 10)->default('VND');
            $table->enum('trang_thai', ['dang_cho', 'thanh_cong', 'that_bai', 'da_hoan'])->default('dang_cho');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('giao_dich');
    }
};
