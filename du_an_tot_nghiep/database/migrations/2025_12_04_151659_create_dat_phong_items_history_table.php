<?php
// database/migrations/2025_xxx_create_dat_phong_items_history_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dat_phong_items_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('dat_phong_id');
            $table->unsignedBigInteger('phong_id')->nullable();
            $table->string('phong_ma')->nullable();
            $table->unsignedBigInteger('loai_phong_id')->nullable();
            $table->decimal('gia_tren_dem', 15, 2)->nullable();
            $table->integer('so_luong')->default(1);
            $table->json('snapshot')->nullable(); // nếu cần lưu snapshot
            $table->timestamps();

            $table->index('dat_phong_id');
            $table->index('phong_id');
            // FK optional (nếu muốn)
            // $table->foreign('dat_phong_id')->references('id')->on('dat_phong')->onDelete('cascade');
            // $table->foreign('phong_id')->references('id')->on('phong')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dat_phong_items_history');
    }
};