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
        Schema::create('thong_bao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nguoi_nhan_id')->constrained('nguoi_dung');
            $table->enum('kenh', ['email', 'sms', 'push']);
            $table->string('ten_template');
            $table->json('payload');
            $table->enum('trang_thai', ['queued', 'sent', 'failed'])->default('queued');
            $table->integer('so_lan_thu')->default(0);
            $table->timestamp('lan_thu_cuoi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thong_bao');
    }
};
