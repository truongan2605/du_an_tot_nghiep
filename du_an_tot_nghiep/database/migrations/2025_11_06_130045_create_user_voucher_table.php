<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_voucher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('voucher_id')->constrained('voucher')->onDelete('cascade');
            $table->timestamp('claimed_at')->nullable(); // thời gian user nhận mã
            $table->timestamps();

            $table->unique(['user_id', 'voucher_id']); // mỗi user chỉ được nhận 1 lần
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_voucher');
    }
};
