<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel `gajis` adalah sisa rancangan awal (0 baris, tidak punya model,
 * tidak pernah di-query). Fungsinya sudah digantikan `penggajian` +
 * `penggajian_details`, sehingga dihapus agar ERD dan database konsisten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('gajis');
    }

    public function down(): void
    {
        Schema::create('gajis', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sopir', 20);
            $table->unsignedBigInteger('periode_id');
            $table->decimal('total_solar', 15, 2)->default(0);
            $table->decimal('total_upah', 15, 2)->default(0);
            $table->decimal('total_sewa_dt', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['kode_sopir', 'periode_id']);
            $table->foreign('kode_sopir')->references('kode_sopir')->on('sopirs')->cascadeOnDelete();
            $table->foreign('periode_id')->references('id')->on('periodes')->cascadeOnDelete();
        });
    }
};
