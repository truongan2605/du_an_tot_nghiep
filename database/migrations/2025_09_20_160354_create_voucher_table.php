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
        Schema::create('voucher', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->enum('type', ['fixed', 'percent']);
            $table->decimal('value', 12, 2);
            $table->integer('qty')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('min_order_amount', 12, 2)->nullable();
            $table->enum('applicable_to', ['all', 'loai_phong', 'phong'])->default('all');
            $table->text('note')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher');
    }
};
