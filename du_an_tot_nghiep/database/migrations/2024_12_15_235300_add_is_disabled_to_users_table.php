<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm cột is_disabled để phân biệt:
     * - is_active = 0: User mới chưa verify email (vẫn cho đăng nhập)
     * - is_disabled = 1: User bị admin vô hiệu hóa (chặn đăng nhập)
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_disabled')->default(false)->after('is_active')
                  ->comment('true = bị admin vô hiệu hóa, không được đăng nhập');
        });

        // Migrate dữ liệu cũ: những user đã verify email nhưng is_active = false
        // thì chuyển sang is_disabled = true, rồi set is_active = true
        DB::table('users')
            ->whereNotNull('email_verified_at')
            ->where('is_active', false)
            ->update([
                'is_disabled' => true,
                'is_active' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rollback: chuyển is_disabled = true về is_active = false
        DB::table('users')
            ->where('is_disabled', true)
            ->update(['is_active' => false]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_disabled');
        });
    }
};
