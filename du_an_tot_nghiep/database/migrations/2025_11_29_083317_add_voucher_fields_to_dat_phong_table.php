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
        Schema::table('dat_phong', function (Blueprint $table) {
            // Thêm cột mã voucher (khóa ngoại tới bảng voucher)
            if (!Schema::hasColumn('dat_phong', 'voucher_id')) {
                $table->unsignedBigInteger('voucher_id')
                    ->nullable()
                    ->after('ma_voucher');
            }

            // Thêm cột số tiền giảm do voucher
            if (!Schema::hasColumn('dat_phong', 'voucher_discount')) {
                $table->decimal('voucher_discount', 12, 2)
                    ->nullable()
                    ->after('voucher_id');
            }

            // Nếu muốn ràng buộc khóa ngoại
            if (Schema::hasTable('voucher')) {
                $table->foreign('voucher_id')
                    ->references('id')
                    ->on('voucher')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong', 'voucher_id')) {
                // cần drop foreign key trước khi drop column
                try {
                    $table->dropForeign('dat_phong_voucher_id_foreign');
                } catch (\Throwable $e) {
                    // Nếu tên FK khác thì bỏ qua
                }

                $table->dropColumn('voucher_id');
            }

            if (Schema::hasColumn('dat_phong', 'voucher_discount')) {
                $table->dropColumn('voucher_discount');
            }
        });
    }
};
