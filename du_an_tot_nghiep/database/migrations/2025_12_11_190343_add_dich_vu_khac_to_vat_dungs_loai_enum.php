<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Thay đổi enum để thêm 'dich_vu_khac'
        DB::statement("ALTER TABLE vat_dungs MODIFY COLUMN loai ENUM('do_an', 'do_dung', 'dich_vu_khac') DEFAULT 'do_dung'");
    }

    public function down(): void
    {
        // Xóa các bản ghi có loai = 'dich_vu_khac' trước khi rollback
        DB::table('vat_dungs')->where('loai', 'dich_vu_khac')->delete();
        
        // Khôi phục enum về trạng thái ban đầu
        DB::statement("ALTER TABLE vat_dungs MODIFY COLUMN loai ENUM('do_an', 'do_dung') DEFAULT 'do_dung'");
    }
};
