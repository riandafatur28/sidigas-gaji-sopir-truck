<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah tabel sudah ada
        if (!Schema::hasTable('penggajian_details')) {
            Schema::create('penggajian_details', function (Blueprint $table) {
                $table->id();

                // PERBAIKAN: Ganti 'penggajians' menjadi 'penggajian'
                $table->foreignId('penggajian_id')->constrained('penggajian')->onDelete('cascade');

                $table->string('kode_tujuan');
                $table->integer('jumlah_rit')->default(0);
                $table->decimal('solar_per_rit', 15, 2)->default(0);
                $table->decimal('upah_per_rit', 15, 2)->default(0);
                $table->decimal('total_solar', 15, 2)->default(0);
                $table->decimal('total_upah', 15, 2)->default(0);
                $table->decimal('sewa_dt', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();

                // Foreign key ke tabel tujuans
                $table->foreign('kode_tujuan')->references('kode_tujuan')->on('tujuans')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('penggajian_details');
    }
};
