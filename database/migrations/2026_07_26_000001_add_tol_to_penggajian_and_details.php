<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. penggajian_details: tol_per_rit & total_tol
        Schema::table('penggajian_details', function (Blueprint $table) {
            if (!Schema::hasColumn('penggajian_details', 'tol_per_rit')) {
                $table->decimal('tol_per_rit', 15, 2)->default(0)->after('sewa_dt');
            }
            if (!Schema::hasColumn('penggajian_details', 'total_tol')) {
                $table->decimal('total_tol', 15, 2)->default(0)->after('tol_per_rit');
            }
        });

        // 2. penggajian: tol
        Schema::table('penggajian', function (Blueprint $table) {
            if (!Schema::hasColumn('penggajian', 'tol')) {
                $table->decimal('tol', 15, 2)->default(0)->after('dt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggajian_details', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian_details', 'tol_per_rit')) {
                $table->dropColumn('tol_per_rit');
            }
            if (Schema::hasColumn('penggajian_details', 'total_tol')) {
                $table->dropColumn('total_tol');
            }
        });

        Schema::table('penggajian', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian', 'tol')) {
                $table->dropColumn('tol');
            }
        });
    }
};
