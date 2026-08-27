<?php

namespace Tests\Unit\Services;

use App\Services\PenggajianService;
use App\Services\SlipBuilderService;
use App\Services\LaporanBuilderService;
use App\Services\PenggajianDataService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PenggajianServiceTest extends TestCase
{
    use RefreshDatabase;

    private PenggajianService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SlipBuilderService::class);
        $this->app->bind(LaporanBuilderService::class);
        $this->app->bind(PenggajianDataService::class);

        $this->service = app(PenggajianService::class);
    }

    public function test_prepare_detail_tujuan_map_parses_request_data(): void
    {
        $request = new Request([
            'detail' => [
                [
                    'kode_tujuan' => 'TUJ-001',
                    'bbm_per_rit' => 50000,
                    'upah_per_rit' => 100000,
                    'tol_per_rit' => 10000,
                    'kompensasi_gagal' => 200000,
                    'lembur_per_rit' => 25000,
                ],
                [
                    'kode_tujuan' => 'TUJ-002',
                    'bbm_per_rit' => 60000,
                    'upah_per_rit' => 120000,
                    'tol_per_rit' => 15000,
                    'kompensasi_gagal' => 250000,
                    'lembur_per_rit' => 30000,
                ],
            ],
        ]);

        $result = $this->service->prepareDetailTujuanMap($request);

        $this->assertArrayHasKey('TUJ-001', $result);
        $this->assertArrayHasKey('TUJ-002', $result);
        $this->assertEquals(50000, $result['TUJ-001']['bbm_per_rit']);
        $this->assertEquals(100000, $result['TUJ-001']['upah_per_rit']);
        $this->assertEquals(10000, $result['TUJ-001']['tol_per_rit']);
        $this->assertEquals(200000, $result['TUJ-001']['kompensasi_gagal']);
        $this->assertEquals(25000, $result['TUJ-001']['lembur_per_rit']);
    }

    public function test_prepare_detail_tujuan_map_handles_missing_fields(): void
    {
        $request = new Request([
            'detail' => [
                [
                    'kode_tujuan' => 'TUJ-001',
                    'bbm_per_rit' => 50000,
                    'upah_per_rit' => 100000,
                ],
            ],
        ]);

        $result = $this->service->prepareDetailTujuanMap($request);

        $this->assertEquals(0, $result['TUJ-001']['tol_per_rit']);
        $this->assertEquals(0, $result['TUJ-001']['kompensasi_gagal']);
        $this->assertEquals(0, $result['TUJ-001']['lembur_per_rit']);
    }

    public function test_prepare_detail_tujuan_map_handles_zero_values(): void
    {
        $request = new Request([
            'detail' => [
                [
                    'kode_tujuan' => 'TUJ-001',
                    'bbm_per_rit' => 0,
                    'upah_per_rit' => '0',
                    'tol_per_rit' => '',
                ],
            ],
        ]);

        $result = $this->service->prepareDetailTujuanMap($request);

        $this->assertEquals(0, $result['TUJ-001']['bbm_per_rit']);
        $this->assertEquals(0, $result['TUJ-001']['upah_per_rit']);
        $this->assertEquals(0, $result['TUJ-001']['tol_per_rit']);
    }
}
