<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        
        DB::statement("ALTER TABLE phong MODIFY COLUMN trang_thai ENUM('trong', 'da_dat', 'dang_o', 'bao_tri', 'khong_su_dung') NOT NULL DEFAULT 'trong'");
    }

    public function down(): void
    {
        
        DB::statement("ALTER TABLE phong MODIFY COLUMN trang_thai ENUM('trong', 'dang_o', 'bao_tri', 'khong_su_dung') NOT NULL DEFAULT 'trong'");
    }
};