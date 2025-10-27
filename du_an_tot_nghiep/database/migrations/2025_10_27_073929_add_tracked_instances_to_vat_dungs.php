<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('vat_dungs', 'tracked_instances')) {
            Schema::table('vat_dungs', function (Blueprint $table) {
                $table->boolean('tracked_instances')->default(false)->after('gia');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('vat_dungs', 'tracked_instances')) {
            Schema::table('vat_dungs', function (Blueprint $table) {
                $table->dropColumn('tracked_instances');
            });
        }
    }
};
