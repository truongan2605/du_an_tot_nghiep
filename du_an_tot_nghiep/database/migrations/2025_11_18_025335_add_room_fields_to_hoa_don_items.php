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
        Schema::table('hoa_don_items', function (Blueprint $table) {
            $table->unsignedBigInteger('phong_id')->nullable()->after('ref_id');
            $table->unsignedBigInteger('loai_phong_id')->nullable()->after('phong_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hoa_don_items', function (Blueprint $table) {
            $table->dropColumn(['phong_id', 'loai_phong_id']);
        });
    }
};
