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
        Schema::table('thong_bao', function (Blueprint $table) {
            // Xóa unique constraint để cho phép gửi thông báo cho nhiều user
            $table->dropUnique('unique_notification_per_user_template_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thong_bao', function (Blueprint $table) {
            // Khôi phục unique constraint
            $table->unique(['nguoi_nhan_id', 'ten_template', 'created_at'], 'unique_notification_per_user_template_time');
        });
    }
};