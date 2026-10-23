<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('validasi_bukti', function (Blueprint $table) {
            $table->string('kode_ritase', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('validasi_bukti', function (Blueprint $table) {
            $table->dropColumn('kode_ritase');
        });
    }
};
