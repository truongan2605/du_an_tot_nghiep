<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('loai_phongs')) {
            Schema::table('loai_phongs', function (Blueprint $table) {
                $table->index('ten', 'loai_phongs_ten_idx');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasTable('loai_phongs')) {
            Schema::table('loai_phongs', function (Blueprint $table) {
                $table->dropIndex('loai_phongs_ten_idx');
            });
        }
    }
};
