<?php

namespace Tests\Unit\Models;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasUniqueKodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_sopir_gets_unique_kode_on_create(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi Santoso']);

        $this->assertStringStartsWith('SPR-', $sopir->kode_sopir);
        $this->assertEquals('SPR-001', $sopir->kode_sopir);
    }

    public function test_tujuan_gets_unique_kode_on_create(): void
    {
        $tujuan = Tujuan::create(['nama' => 'Jombang']);

        $this->assertStringStartsWith('TUJ-', $tujuan->kode_tujuan);
        $this->assertEquals('TUJ-001', $tujuan->kode_tujuan);
    }

    public function test_periode_gets_unique_kode_on_create(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $this->assertStringStartsWith('PER-', $periode->kode_periode);
        $this->assertEquals('PER-001', $periode->kode_periode);
    }

    public function test_ritase_gets_unique_kode_on_create(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);
        $tujuan = Tujuan::create(['nama' => 'Jombang']);
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $ritase = Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $this->assertStringStartsWith('RIT-', $ritase->kode_ritase);
        $this->assertEquals('RIT-001', $ritase->kode_ritase);
    }

    public function test_sequential_sopir_codes_increment(): void
    {
        $sopir1 = Sopir::create(['nama' => 'Budi']);
        $sopir2 = Sopir::create(['nama' => 'Agus']);
        $sopir3 = Sopir::create(['nama' => 'Candra']);

        $this->assertEquals('SPR-001', $sopir1->kode_sopir);
        $this->assertEquals('SPR-002', $sopir2->kode_sopir);
        $this->assertEquals('SPR-003', $sopir3->kode_sopir);
    }

    public function test_sequential_tujuan_codes_increment(): void
    {
        $tujuan1 = Tujuan::create(['nama' => 'Jombang']);
        $tujuan2 = Tujuan::create(['nama' => 'Kediri']);

        $this->assertEquals('TUJ-001', $tujuan1->kode_tujuan);
        $this->assertEquals('TUJ-002', $tujuan2->kode_tujuan);
    }
}
