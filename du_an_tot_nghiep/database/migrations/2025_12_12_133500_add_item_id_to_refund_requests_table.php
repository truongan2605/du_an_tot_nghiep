<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm dat_phong_item_id để liên kết refund request với từng phòng bị hủy
     */
    public function up(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            // Liên kết với phòng cụ thể bị hủy (optional - null nếu hủy toàn bộ booking)
            $table->unsignedBigInteger('dat_phong_item_id')->nullable()->after('dat_phong_id');
            
            // Loại hoàn tiền: 'full_booking' hoặc 'single_room'
            $table->string('refund_type', 20)->default('full_booking')->after('dat_phong_item_id');
            
            // Index để tìm kiếm nhanh
            $table->index(['dat_phong_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refund_requests', function (Blueprint $table) {
            $table->dropColumn(['dat_phong_item_id', 'refund_type']);
        });
    }
};
