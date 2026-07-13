<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ritases', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ritase')->unique();
            $table->foreignId('periode_id')->constrained('periodes')->onDelete('cascade');
            $table->string('kode_sopir');
            $table->string('kode_tujuan');
            $table->date('tanggal');
            $table->enum('waktu', ['pagi', 'malam']);
            $table->enum('kabupaten', ['Nganjuk', 'Kediri', 'Kota Kediri', 'Jombang', 'Lainnya']);
            $table->enum('status', ['valid', 'pending', 'gagal_produksi'])->default('pending');
            $table->decimal('nominal_kompensasi', 15, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->foreign('kode_sopir')->references('kode_sopir')->on('sopirs')->onDelete('cascade');
            $table->foreign('kode_tujuan')->references('kode_tujuan')->on('tujuans')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ritases');
    }
};
