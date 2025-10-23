<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->string('spec_signature_hash', 64)->nullable()->after('loai_phong_id');
        });
    }

    public function down(): void
    {
        Schema::table('dat_phong_item', function (Blueprint $table) {
            $table->dropColumn('spec_signature_hash');
        });
    }
};
