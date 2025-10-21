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
        Schema::table('giu_phong', function (Blueprint $table) {
            if (!Schema::hasColumn('giu_phong', 'dat_phong_id')) {
                $table->unsignedBigInteger('dat_phong_id')->nullable()->after('id');
            }
            $table->string('spec_signature_hash', 64)->nullable()->after('loai_phong_id');
            $table->json('meta')->nullable()->after('spec_signature_hash');
        });
    }


    public function down(): void
    {
        Schema::table('giu_phong', function (Blueprint $table) {
            $table->dropColumn('spec_signature_hash');
            $table->dropColumn('meta');
            if (Schema::hasColumn('giu_phong', 'dat_phong_id')) {
                $table->dropColumn('dat_phong_id');
            }
        });
    }
};
