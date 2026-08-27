<?php

namespace Tests\Unit\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\RitaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RitaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private RitaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RitaseService();
    }

    // === cleanTujuan tests ===

    public function test_clean_tujuan_removes_filler_words(): void
    {
        $this->assertEquals('Jombang', $this->service->cleanTujuan('Paket Overlay Jombang'));
        $this->assertEquals('Kediri', $this->service->cleanTujuan('Patching Kediri'));
        $this->assertEquals('Blitar', $this->service->cleanTujuan('CMM Blitar'));
    }

    public function test_clean_tujuan_returns_original_if_no_fillers(): void
    {
        $this->assertEquals('Blitar', $this->service->cleanTujuan('Blitar'));
        $this->assertEquals('Surabaya Kota', $this->service->cleanTujuan('Surabaya Kota'));
    }

    public function test_clean_tujuan_returns_question_mark_for_null(): void
    {
        $this->assertEquals('?', $this->service->cleanTujuan(null));
    }

    public function test_clean_tujuan_returns_question_mark_for_empty(): void
    {
        $this->assertEquals('?', $this->service->cleanTujuan(''));
    }

    // === hitungDT tests ===

    public function test_hitung_dt_returns_zero_for_gagal_produksi(): void
    {
        $request = new Request([
            'status' => 'gagal_produksi',
            'kode_sopir' => 'SPR-001',
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Jombang',
            'waktu' => 'pagi',
        ]);

        $result = $this->service->hitungDT($request);

        $this->assertEquals(0, $result);
    }

    public function test_hitung_dt_returns_value_for_first_rit(): void
    {
        $request = new Request([
            'status' => 'valid',
            'kode_sopir' => 'SPR-001',
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Jombang',
            'waktu' => 'pagi',
        ]);

        $result = $this->service->hitungDT($request);

        $this->assertEquals(330000, $result);
    }

    public function test_hitung_dt_returns_zero_for_single_dt_regency_second_rit(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);
        $tujuan = Tujuan::create(['nama' => 'Jombang']);
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        // First rit — should get DT
        $firstRequest = new Request([
            'status' => 'valid',
            'kode_sopir' => $sopir->kode_sopir,
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Jombang',
            'waktu' => 'pagi',
        ]);
        $this->assertEquals(330000, $this->service->hitungDT($firstRequest));

        // Second rit same kab+waktu — should get 0 for Jombang (single_dt_regency)
        // Use DB::table to bypass model date cast (SQLite stores '2026-07-22 00:00:00')
        DB::table('ritases')->insert([
            'kode_ritase' => 'RIT-TEST-001',
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $request = new Request([
            'status' => 'valid',
            'kode_sopir' => $sopir->kode_sopir,
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Jombang',
            'waktu' => 'pagi',
        ]);

        $result = $this->service->hitungDT($request);

        $this->assertEquals(0, $result);
    }

    public function test_hitung_dt_returns_value_for_non_single_dt_regency_second_rit(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);
        $tujuan = Tujuan::create(['nama' => 'Blitar']);
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        // First rit — "Lainnya" is NOT a single_dt_regency, so gets DT
        $firstRequest = new Request([
            'status' => 'valid',
            'kode_sopir' => $sopir->kode_sopir,
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Lainnya',
            'waktu' => 'pagi',
        ]);
        $this->assertEquals(330000, $this->service->hitungDT($firstRequest));

        // Second rit same kab+waktu — "Lainnya" still gets DT (not single_dt_regency)
        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Lainnya',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $request = new Request([
            'status' => 'valid',
            'kode_sopir' => $sopir->kode_sopir,
            'tanggal' => '2026-07-22',
            'kabupaten' => 'Lainnya',
            'waktu' => 'pagi',
        ]);

        $result = $this->service->hitungDT($request);

        $this->assertEquals(330000, $result);
    }
}
