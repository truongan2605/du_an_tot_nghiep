<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->decimal('refund_amount', 12, 2)->nullable()->after('deposit_amount');
            $table->integer('refund_percentage')->nullable()->after('refund_amount');
            $table->timestamp('cancelled_at')->nullable()->after('updated_at');
            $table->string('cancellation_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            $table->dropColumn(['refund_amount', 'refund_percentage', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
