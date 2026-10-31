<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Backfill: pakai nilai cache yang masih ada (kalau ada) supaya
        // tidak ada nilai yang hilang saat migrasi; kalau cache kosong,
        // pakai default dari config.
        $defaults = [
            'dt_nominal' => (int) config('dt.value', 330000),
            'dt_single_regencies' => (array) config('dt.single_dt_regencies', []),
            'aturan_validasi_enabled' => false,
        ];

        foreach ($defaults as $key => $default) {
            $cached = Cache::get($key);
            $value = $cached !== null ? $cached : $default;
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => json_encode($value), 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
