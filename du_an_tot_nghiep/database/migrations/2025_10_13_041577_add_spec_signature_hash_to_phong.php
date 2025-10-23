<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSpecSignatureHashToPhong extends Migration
{
    public function up()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->string('spec_signature_hash')->nullable()->index()->after('gia_cuoi_cung');
        });
    }

    public function down()
    {
        Schema::table('phong', function (Blueprint $table) {
            $table->dropColumn('spec_signature_hash');
        });
    }
}
