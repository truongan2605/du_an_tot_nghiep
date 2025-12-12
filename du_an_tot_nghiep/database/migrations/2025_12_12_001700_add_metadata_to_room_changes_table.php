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
        Schema::table('room_changes', function (Blueprint $table) {
            // Metadata để lưu thông tin voucher được kế thừa khi đổi phòng
            $table->json('metadata')->nullable()->after('payment_info')
                ->comment('Lưu inherited_voucher và các thông tin khác');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_changes', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
