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
        // Xóa bảng cong_viec_bao_tri trước vì nó có foreign key đến vat_dung_phong
        Schema::dropIfExists('cong_viec_bao_tri');
        
        // Xóa bảng vat_dung_phong
        Schema::dropIfExists('vat_dung_phong');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tạo lại bảng vat_dung_phong
        Schema::create('vat_dung_phong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong');
            $table->string('sku');
            $table->string('ten');
            $table->enum('tinh_trang', ['tot', 'hong', 'mat'])->default('tot');
            $table->integer('so_luong')->default(1);
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });

        // Tạo lại bảng cong_viec_bao_tri
        Schema::create('cong_viec_bao_tri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phong_id')->constrained('phong');
            $table->foreignId('vat_dung_id')->nullable()->constrained('vat_dung_phong');
            $table->foreignId('nguoi_bao_cao')->constrained('nguoi_dung');
            $table->foreignId('nguoi_duoc_gan')->nullable()->constrained('nguoi_dung');
            $table->integer('uu_tien')->default(1);
            $table->text('mo_ta');
            $table->enum('trang_thai', ['mo', 'dang_xu_ly', 'hoan_thanh', 'da_huy'])->default('mo');
            $table->decimal('chi_phi_du_kien', 12, 2)->nullable();
            $table->timestamps();
        });
    }
};
