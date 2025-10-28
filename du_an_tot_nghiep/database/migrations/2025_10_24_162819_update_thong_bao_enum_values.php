<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update kenh enum to include 'in_app'
        DB::statement("ALTER TABLE thong_bao MODIFY COLUMN kenh ENUM('email', 'sms', 'push', 'in_app')");
        
        // Update trang_thai enum to include 'pending'
        DB::statement("ALTER TABLE thong_bao MODIFY COLUMN trang_thai ENUM('queued', 'sent', 'failed', 'pending', 'read') DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert kenh enum to original values
        DB::statement("ALTER TABLE thong_bao MODIFY COLUMN kenh ENUM('email', 'sms', 'push')");
        
        // Revert trang_thai enum to original values
        DB::statement("ALTER TABLE thong_bao MODIFY COLUMN trang_thai ENUM('queued', 'sent', 'failed') DEFAULT 'queued'");
    }
};
