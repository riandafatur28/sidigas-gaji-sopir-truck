<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE ritases MODIFY COLUMN kabupaten ENUM(
            'Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang',
            'Blitar', 'Kota Blitar', 'Ngawi', 'Kota Ngawi',
            'Lainnya'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE ritases MODIFY COLUMN kabupaten ENUM(
            'Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang', 'Lainnya'
        ) NOT NULL");
    }
};
