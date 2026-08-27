<?php

namespace Tests\Unit\Services;

use App\Models\Periode;
use App\Models\Penggajian;
use App\Models\PenggajianDetail;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\SlipBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlipBuilderServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlipBuilderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlipBuilderService();
    }

    public function test_build_slip_data_returns_null_for_nonexistent_sopir(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $result = $this->service->buildSlipData($periode->id, 'SPR-999');

        $this->assertNull($result);
    }

    public function test_merge_duplicate_rit_entries_combines_same_day_same_tujuan(): void
    {
        $entries = [
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 1,
                'total_rit_hari' => 1,
                'solar' => 50000,
                'upah' => 100000,
                'jumlah' => 150000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 10000,
            ],
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 2,
                'total_rit_hari' => 1,
                'solar' => 50000,
                'upah' => 100000,
                'jumlah' => 150000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 10000,
            ],
        ];

        $result = $this->service->mergeDuplicateRitEntries($entries);

        $this->assertCount(1, $result);
        $this->assertEquals(100000, $result[0]['solar']);
        $this->assertEquals(200000, $result[0]['upah']);
        $this->assertEquals(300000, $result[0]['jumlah']);
        $this->assertEquals('2x Jombang', $result[0]['tujuan']);
        $this->assertEquals(2, $result[0]['rit_count']);
    }

    public function test_merge_duplicate_rit_entries_preserves_different_tujuan(): void
    {
        $entries = [
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 1,
                'total_rit_hari' => 1,
                'solar' => 50000,
                'upah' => 100000,
                'jumlah' => 150000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 10000,
            ],
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 2,
                'total_rit_hari' => 1,
                'solar' => 60000,
                'upah' => 120000,
                'jumlah' => 180000,
                'tujuan' => 'Kediri',
                'kode_tujuan' => 'TUJ-002',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 15000,
            ],
        ];

        $result = $this->service->mergeDuplicateRitEntries($entries);

        $this->assertCount(2, $result);
        $this->assertEquals('Jombang', $result[0]['tujuan']);
        $this->assertEquals('Kediri', $result[1]['tujuan']);
    }

    public function test_merge_duplicate_rit_entries_keeps_gagal_separate(): void
    {
        $entries = [
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 1,
                'total_rit_hari' => 1,
                'solar' => 50000,
                'upah' => 100000,
                'jumlah' => 150000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 10000,
            ],
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 2,
                'total_rit_hari' => 1,
                'solar' => 0,
                'upah' => 0,
                'jumlah' => 200000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => true,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 0,
                'tol' => 0,
            ],
        ];

        $result = $this->service->mergeDuplicateRitEntries($entries);

        $this->assertCount(2, $result);
        $this->assertFalse($result[0]['is_gagal']);
        $this->assertTrue($result[1]['is_gagal']);
    }

    public function test_merge_duplicate_rit_entries_returns_empty_array(): void
    {
        $result = $this->service->mergeDuplicateRitEntries([]);
        $this->assertCount(0, $result);
    }

    public function test_merge_duplicate_rit_entries_returns_single_entry_unchanged(): void
    {
        $entries = [
            [
                'tanggal' => '2026-07-22',
                'hari' => 'Selasa',
                'rit_ke' => 1,
                'total_rit_hari' => 1,
                'solar' => 50000,
                'upah' => 100000,
                'jumlah' => 150000,
                'tujuan' => 'Jombang',
                'kode_tujuan' => 'TUJ-001',
                'is_gagal' => false,
                'is_lembur' => false,
                'upah_lembur' => 0,
                'dt' => 330000,
                'tol' => 10000,
            ],
        ];

        $result = $this->service->mergeDuplicateRitEntries($entries);

        $this->assertCount(1, $result);
        // Single entry returned as-is (no merge needed, no rit_count added)
        $this->assertEquals(50000, $result[0]['solar']);
        $this->assertEquals(100000, $result[0]['upah']);
    }
}
