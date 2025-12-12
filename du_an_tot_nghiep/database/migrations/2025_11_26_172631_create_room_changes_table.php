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
        Schema::create('room_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dat_phong_id')->constrained('dat_phong')->onDelete('cascade');
            $table->foreignId('old_room_id')->constrained('phong')->onDelete('restrict');
            $table->foreignId('new_room_id')->constrained('phong')->onDelete('restrict');
            $table->decimal('old_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->decimal('price_difference', 12, 2); // Can be negative for downgrade
            $table->integer('nights');
            $table->text('change_reason')->nullable();
            $table->enum('changed_by_type', ['customer', 'staff']);
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('payment_info')->nullable(); // Store VNPay transaction details
            $table->timestamps();
            
            // Indexes for performance
            $table->index('dat_phong_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_changes');
    }
};
