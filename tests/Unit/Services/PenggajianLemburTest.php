<?php

namespace Tests\Unit\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\LaporanBuilderService;
use App\Services\PenggajianDataService;
use App\Services\PenggajianService;
use App\Services\SlipBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PenggajianLemburTest extends TestCase
{
    use RefreshDatabase;

    private PenggajianService $service;
    private Periode $periode;
    private Tujuan $tujuan;
    private array $sopirs = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(SlipBuilderService::class);
        $this->app->bind(LaporanBuilderService::class);
        $this->app->bind(PenggajianDataService::class);
        $this->service = app(PenggajianService::class);

        $this->periode = Periode::create([
            'nama_periode' => 'PER-TEST',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
        $this->tujuan = Tujuan::create(['nama' => 'Paket candirejo nganjuk', 'status' => 'aktif']);

        foreach (['Gun' => true, 'Didik' => false] as $nama => $lembur) {
            $sopir = Sopir::create(['nama' => $nama, 'status' => 'aktif']);
            $this->sopirs[$nama] = $sopir;
            Ritase::create([
                'periode_id' => $this->periode->id,
                'kode_sopir' => $sopir->kode_sopir,
                'kode_tujuan' => $this->tujuan->kode_tujuan,
                'tanggal' => '2026-11-04',
                'waktu' => 'pagi',
                'kabupaten' => 'Nganjuk',
                'status' => 'valid',
                'dt' => 0,
                'upah_sopir' => 0,
                'is_lembur' => $lembur,
                'upah_lembur' => 0,
            ]);
        }
    }

    private function storeWithLembur(float $lemburPerRit): void
    {
        $request = new Request([
            'periode_id' => $this->periode->id,
            'detail' => [[
                'kode_tujuan' => $this->tujuan->kode_tujuan,
                'bbm_per_rit' => 0,
                'upah_per_rit' => 0,
                'tol_per_rit' => 0,
                'kompensasi_gagal' => 0,
                'lembur_per_rit' => $lemburPerRit,
            ]],
        ]);
        $this->service->storePenggajian($request);
    }

    private function ritaseFor(string $nama): Ritase
    {
        return Ritase::where('kode_sopir', $this->sopirs[$nama]->kode_sopir)->firstOrFail();
    }

    public function test_lembur_nominal_only_goes_to_flagged_rows(): void
    {
        $this->storeWithLembur(50000);

        $this->assertTrue((bool) $this->ritaseFor('Gun')->is_lembur);
        $this->assertSame(50000.0, (float) $this->ritaseFor('Gun')->upah_lembur);
        // Didik was not flagged lembur: the per-tujuan tick must not
        // turn him lembur, and must not pay him lembur.
        $this->assertFalse((bool) $this->ritaseFor('Didik')->is_lembur);
        $this->assertSame(0.0, (float) $this->ritaseFor('Didik')->upah_lembur);
    }

    public function test_zero_lembur_nominal_clears_pay_but_keeps_the_flag(): void
    {
        $this->storeWithLembur(50000);
        $this->storeWithLembur(0);

        $this->assertTrue((bool) $this->ritaseFor('Gun')->is_lembur);
        $this->assertSame(0.0, (float) $this->ritaseFor('Gun')->upah_lembur);
    }
}
