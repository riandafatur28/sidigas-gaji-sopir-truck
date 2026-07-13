<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (!Schema::hasColumn('penggajian', 'kompensasi_gagal')) {
                $table->decimal('kompensasi_gagal', 15, 2)->default(0)->after('dt');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian', 'kompensasi_gagal')) {
                $table->dropColumn('kompensasi_gagal');
            }
        });
    }
};
