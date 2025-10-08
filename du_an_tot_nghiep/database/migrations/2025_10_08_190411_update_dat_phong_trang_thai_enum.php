<?php 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDatPhongTrangThaiEnum extends Migration
{
    public function up()
    {
        
        $validStatuses = ['dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan', 'da_gan_phong', 'da_huy', 'dang_su_dung'];
        DB::table('dat_phong')
            ->whereNotIn('trang_thai', $validStatuses)
            ->update(['trang_thai' => 'da_huy']);

      
        DB::statement("ALTER TABLE dat_phong MODIFY COLUMN trang_thai ENUM('dang_cho', 'dang_cho_xac_nhan', 'da_xac_nhan', 'da_gan_phong', 'da_huy', 'dang_su_dung')");
    }

    public function down()
    {
        
        DB::statement("ALTER TABLE dat_phong MODIFY COLUMN trang_thai VARCHAR(50)");
    }
}