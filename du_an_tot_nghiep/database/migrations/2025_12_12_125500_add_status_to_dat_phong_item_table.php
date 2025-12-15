<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Thêm trạng thái cho từng phòng trong booking để hỗ trợ hủy từng phòng riêng lẻ
     */
    public function up(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            // Trạng thái của phòng: active (đang sử dụng), cancelled (đã hủy), completed (hoàn thành)
            $table->string('trang_thai', 20)->default('active')->after('voucher_allocated');
            
            // Thông tin hoàn tiền khi hủy từng phòng
            $table->decimal('refund_amount', 12, 2)->nullable()->after('trang_thai');
            $table->integer('refund_percentage')->nullable()->after('refund_amount');
            $table->timestamp('cancelled_at')->nullable()->after('refund_percentage');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->dropColumn(['trang_thai', 'refund_amount', 'refund_percentage', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
