<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('validasi_bukti', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sopir', 20)->nullable();
            $table->string('nama_sopir', 100);
            $table->boolean('sopir_baru')->default(false);
            $table->string('kode_tujuan', 20)->nullable();
            $table->string('nama_tujuan', 100);
            $table->boolean('tujuan_baru')->default(false);
            $table->string('foto');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->dateTime('waktu_foto');
            $table->date('tanggal');
            $table->unsignedBigInteger('periode_id')->nullable();
            $table->text('catatan')->nullable();
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            $table->text('catatan_mitra')->nullable();
            $table->timestamps();

            $table->foreign('kode_sopir')->references('kode_sopir')->on('sopirs')->nullOnDelete();
            $table->foreign('kode_tujuan')->references('kode_tujuan')->on('tujuans')->nullOnDelete();
            $table->foreign('periode_id')->references('id')->on('periodes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('validasi_bukti');
    }
};
