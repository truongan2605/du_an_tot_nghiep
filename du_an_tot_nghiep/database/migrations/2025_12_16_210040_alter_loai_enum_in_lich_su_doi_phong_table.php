<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE lich_su_doi_phong 
            MODIFY loai ENUM('nang_cap', 'ha_cap', 'giu_nguyen') 
            NULL
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE lich_su_doi_phong 
            MODIFY loai ENUM('nang_cap', 'ha_cap') 
            NULL
        ");
    }
};
