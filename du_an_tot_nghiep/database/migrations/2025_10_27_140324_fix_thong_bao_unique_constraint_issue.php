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
        // Xóa foreign key constraints trước
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = 'thong_bao' 
            AND CONSTRAINT_NAME != 'PRIMARY'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        foreach ($foreignKeys as $fk) {
            try {
                DB::statement("ALTER TABLE thong_bao DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                echo "Dropped foreign key: {$fk->CONSTRAINT_NAME}\n";
            } catch (Exception $e) {
                echo "Could not drop foreign key {$fk->CONSTRAINT_NAME}: " . $e->getMessage() . "\n";
            }
        }
        
        // Xóa unique index
        try {
            DB::statement('ALTER TABLE thong_bao DROP INDEX unique_notification_per_user_template_time');
            echo "Dropped unique index: unique_notification_per_user_template_time\n";
        } catch (Exception $e) {
            echo "Could not drop index: " . $e->getMessage() . "\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Không cần rollback vì chúng ta đã xóa constraint có vấn đề
    }
};