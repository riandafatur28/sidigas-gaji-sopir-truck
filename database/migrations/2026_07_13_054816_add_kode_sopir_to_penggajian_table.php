<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            // Tambahkan kode_sopir jika belum ada
            if (!Schema::hasColumn('penggajian', 'kode_sopir')) {
                $table->string('kode_sopir')->after('id');
                $table->foreign('kode_sopir')->references('kode_sopir')->on('sopirs')->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian', 'kode_sopir')) {
                $table->dropForeign(['kode_sopir']);
                $table->dropColumn('kode_sopir');
            }
        });
    }
};
