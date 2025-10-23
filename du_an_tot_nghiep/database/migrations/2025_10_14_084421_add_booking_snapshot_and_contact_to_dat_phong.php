<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBookingSnapshotAndContactToDatPhong extends Migration
{
    public function up()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (!Schema::hasColumn('dat_phong', 'contact_name')) {
                $table->string('contact_name')->nullable()->after('updated_at');
            }
            if (!Schema::hasColumn('dat_phong', 'contact_address')) {
                $table->text('contact_address')->nullable()->after('contact_name');
            }
            if (!Schema::hasColumn('dat_phong', 'contact_phone')) {
                $table->string('contact_phone', 50)->nullable()->after('contact_address');
            }
            if (!Schema::hasColumn('dat_phong', 'snapshot_meta')) {
                try {
                    $table->json('snapshot_meta')->nullable()->after('contact_phone');
                } catch (\Throwable $e) {
                    $table->text('snapshot_meta')->nullable()->after('contact_phone');
                }
            }
        });
    }

    public function down()
    {
        Schema::table('dat_phong', function (Blueprint $table) {
            if (Schema::hasColumn('dat_phong', 'snapshot_meta')) $table->dropColumn('snapshot_meta');
            if (Schema::hasColumn('dat_phong', 'contact_phone')) $table->dropColumn('contact_phone');
            if (Schema::hasColumn('dat_phong', 'contact_address')) $table->dropColumn('contact_address');
            if (Schema::hasColumn('dat_phong', 'contact_name')) $table->dropColumn('contact_name');
        });
    }
}
