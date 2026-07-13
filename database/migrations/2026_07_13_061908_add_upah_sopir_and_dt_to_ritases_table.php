<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            // Tambahkan kolom upah_sopir jika belum ada
            if (!Schema::hasColumn('ritases', 'upah_sopir')) {
                $table->decimal('upah_sopir', 15, 2)->default(0)->after('kode_tujuan');
            }

            // Tambahkan kolom dt jika belum ada
            if (!Schema::hasColumn('ritases', 'dt')) {
                $table->decimal('dt', 15, 2)->default(0)->after('upah_sopir');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            if (Schema::hasColumn('ritases', 'upah_sopir')) {
                $table->dropColumn('upah_sopir');
            }
            if (Schema::hasColumn('ritases', 'dt')) {
                $table->dropColumn('dt');
            }
        });
    }
};
