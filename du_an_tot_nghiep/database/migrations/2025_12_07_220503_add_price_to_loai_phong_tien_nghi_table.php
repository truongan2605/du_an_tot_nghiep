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
        Schema::table('loai_phong_tien_nghi', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('tien_nghi_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loai_phong_tien_nghi', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
