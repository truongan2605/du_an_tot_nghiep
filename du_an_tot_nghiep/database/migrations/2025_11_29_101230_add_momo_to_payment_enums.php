<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'momo' to giao_dich.nha_cung_cap (currently it's a string, not enum)
        // No change needed as nha_cung_cap is VARCHAR
        
        // Add 'momo' to dat_phong.phuong_thuc ENUM
        DB::statement("ALTER TABLE dat_phong MODIFY COLUMN phuong_thuc ENUM('vnpay', 'tien_mat', 'chuyen_khoan', 'momo') DEFAULT 'tien_mat'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove 'momo' from dat_phong.phuong_thuc ENUM
        DB::statement("ALTER TABLE dat_phong MODIFY COLUMN phuong_thuc ENUM('vnpay', 'tien_mat', 'chuyen_khoan') DEFAULT 'tien_mat'");
    }
};
