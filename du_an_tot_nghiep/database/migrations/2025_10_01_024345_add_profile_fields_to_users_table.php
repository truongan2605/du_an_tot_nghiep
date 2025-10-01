<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            if (! Schema::hasColumn('users', 'country')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('country')->nullable()->after('so_dien_thoai');
                });
            }

            if (! Schema::hasColumn('users', 'dob')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->date('dob')->nullable()->after('country');
                });
            }

            if (! Schema::hasColumn('users', 'gender')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('dob');
                });
            }

            if (! Schema::hasColumn('users', 'address')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->text('address')->nullable()->after('gender');
                });
            }

            if (! Schema::hasColumn('users', 'avatar')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->string('avatar')->nullable()->after('address');
                });
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (Schema::hasColumn('users', 'avatar')) {
                    $table->dropColumn('avatar');
                }
                if (Schema::hasColumn('users', 'address')) {
                    $table->dropColumn('address');
                }
                if (Schema::hasColumn('users', 'gender')) {
                    $table->dropColumn('gender');
                }
                if (Schema::hasColumn('users', 'dob')) {
                    $table->dropColumn('dob');
                }
                if (Schema::hasColumn('users', 'country')) {
                    $table->dropColumn('country');
                }
            });
        }
    }
};
