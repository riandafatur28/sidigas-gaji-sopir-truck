<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            $table->enum('kabupaten', [
                'Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang',
                'Blitar', 'Kota Blitar', 'Ngawi', 'Kota Ngawi',
                'Lainnya',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            $table->enum('kabupaten', ['Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang', 'Lainnya'])->change();
        });
    }
};
