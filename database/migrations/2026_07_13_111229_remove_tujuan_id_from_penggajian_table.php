<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            if (Schema::hasColumn('penggajian', 'tujuan_id')) {
                $table->dropForeign(['tujuan_id']);
                $table->dropColumn('tujuan_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penggajian', function (Blueprint $table) {
            $table->foreignId('tujuan_id')->nullable()->constrained()->onDelete('cascade');
        });
    }
};
