<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lepas CHECK enum kabupaten (SQLite: ->change() memicu rebuild table).
        // Sebelumnya hanya Nganjuk..Ngawi+Lainnya; input parser "Ponorogo" ditolak.
        Schema::table('ritases', function (Blueprint $table) {
            $table->string('kabupaten', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('ritases', function (Blueprint $table) {
            $table->enum('kabupaten', [
                'Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang',
                'Blitar', 'Kota Blitar', 'Ngawi', 'Kota Ngawi',
                'Lainnya',
            ])->change();
        });
    }
};
