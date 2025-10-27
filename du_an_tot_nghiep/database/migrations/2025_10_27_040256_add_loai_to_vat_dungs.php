<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vat_dungs', function (Blueprint $table) {
            $table->enum('loai', ['do_an', 'do_dung'])->default('do_dung')->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('vat_dungs', function (Blueprint $table) {
            $table->dropColumn('loai');
        });
    }
};

