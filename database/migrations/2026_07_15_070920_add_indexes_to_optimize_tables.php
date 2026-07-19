<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIdx('ritases', 'idx_ritase_tanggal', 'tanggal');
        $this->addIdx('ritases', 'idx_ritase_kode_sopir', 'kode_sopir');
        $this->addIdx('ritases', 'idx_ritase_kode_tujuan', 'kode_tujuan');
        $this->addIdx('ritases', 'idx_ritase_status', 'status');
        $this->addIdx('ritases', 'idx_ritase_periode_id', 'periode_id');
        $this->addIdx('ritases', 'idx_ritase_waktu', 'waktu');
        $this->addIdx('validasi_bukti', 'idx_validasi_status', 'status');
        $this->addIdx('validasi_bukti', 'idx_validasi_tanggal', 'tanggal');
        $this->addIdx('validasi_bukti', 'idx_validasi_kode_sopir', 'kode_sopir');
        $this->addIdx('validasi_bukti', 'idx_validasi_periode_id', 'periode_id');
        $this->addIdx('penggajian', 'idx_penggajian_periode_id', 'periode_id');
        $this->addIdx('penggajian', 'idx_penggajian_kode_sopir', 'kode_sopir');
        $this->addIdx('penggajian_details', 'idx_detail_penggajian_id', 'penggajian_id');
        $this->addIdx('penggajian_details', 'idx_detail_kode_tujuan', 'kode_tujuan');
        $this->addIdx('periodes', 'idx_periode_status', 'status');
        $this->addIdx('periodes', 'idx_periode_tanggal_mulai', 'tanggal_mulai');
        $this->addIdx('periodes', 'idx_periode_tanggal_selesai', 'tanggal_selesai');
        $this->addIdx('sopirs', 'idx_sopir_status', 'status');
        $this->addIdx('tujuans', 'idx_tujuan_status', 'status');
    }

    public function down(): void
    {
        // indexes will be kept; dropping them has no benefit
    }

    private function addIdx(string $table, string $name, string $column): void
    {
        try {
            DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$column}`)");
        } catch (\Exception $e) {
            // skip if index already exists or FK constraint
        }
    }
};
