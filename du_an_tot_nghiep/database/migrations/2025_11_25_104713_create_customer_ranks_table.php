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
        Schema::create('customer_ranks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->unique(); // mỗi user 1 bản ghi

            // tổng tiền từ các đơn đặt phòng hoàn thành (VND)
            $table->unsignedBigInteger('total_amount')->default(0);

            // none / bac / vang / kim_cuong
            $table->enum('rank', ['none', 'bac', 'vang', 'kim_cuong'])
                  ->default('none');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_ranks');
    }
};
