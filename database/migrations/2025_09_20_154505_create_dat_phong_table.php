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
        Schema::create('dat_phong', function (Blueprint $table) {
            $table->id();
            $table->string('ma_tham_chieu')->unique();
            $table->foreignId('nguoi_dung_id')->constrained('nguoi_dung');
            $table->enum('trang_thai', ['dang_cho', 'da_xac_nhan', 'da_nhan_phong', 'hoan_thanh', 'da_huy', 'het_han'])->default('dang_cho');
            $table->date('ngay_nhan_phong');
            $table->date('ngay_tra_phong');
            $table->integer('so_khach');
            $table->decimal('tong_tien', 12, 2);
            $table->string('don_vi_tien', 10)->default('VND');
            $table->boolean('can_thanh_toan')->default(false);
            $table->foreignId('created_by')->constrained('nguoi_dung');
            $table->enum('phuong_thuc', ['vnpay', 'tien_mat', 'chuyen_khoan'])->default('tien_mat');
            $table->string('ma_voucher')->nullable();
            $table->decimal('discount_amount', 12, 2)->nullable();
            $table->decimal('snapshot_total', 12, 2)->nullable();
            $table->enum('source', ['web', 'phone', 'staff', 'ota'])->default('web');
            $table->text('ghi_chu')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dat_phong');
    }
};
