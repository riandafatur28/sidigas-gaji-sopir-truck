<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Soft delete untuk semua tabel inti.
 *
 * Alasan: hard delete pada data gaji/ritase/periode berbahaya (FK cascade
 * menghapus turunan secara diam-diam) dan tidak bisa di-undo. Dengan
 * deleted_at, penghapusan bisa ditinjau & dipulihkan.
 */
return new class extends Migration
{
    private const TABLES = [
        'users',
        'sopirs',
        'tujuans',
        'periodes',
        'ritases',
        'penggajian',
        'penggajian_details',
        'validasi_bukti',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'deleted_at')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropSoftDeletes();
                });
            }
        }
    }
};
