<?php



use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->datetime('ngay_nhan_phong')->change();
            $table->datetime('ngay_tra_phong')->change();
            $table->char('don_vi_tien', 3)->default('VND')->change();
            $table->enum('trang_thai', ['dang_cho', 'da_xac_nhan', 'da_gan_phong', 'da_nhan_phong', 'hoan_thanh', 'da_huy', 'het_han'])->default('dang_cho')->change();
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->date('ngay_nhan_phong')->change();
            $table->date('ngay_tra_phong')->change();
            $table->string('don_vi_tien', 10)->default('VND')->change();
            $table->enum('trang_thai', ['dang_cho', 'da_xac_nhan', 'da_nhan_phong', 'hoan_thanh', 'da_huy', 'het_han'])->default('dang_cho')->change();
        });
    }
};
