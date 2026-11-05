<?php

namespace Tests\Unit\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\RitaseCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RitaseCreatorLemburTest extends TestCase
{
    use RefreshDatabase;

    public function test_lembur_flags_base_ritase_without_creating_duplicates(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'PER-TEST',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
        $tujuan = Tujuan::create(['nama' => 'Paket candirejo nganjuk', 'status' => 'aktif']);

        $sopirs = [];
        foreach (['Gun', 'Toni', 'Didik'] as $nama) {
            $sopirs[$nama] = Sopir::create(['nama' => $nama, 'status' => 'aktif']);
        }

        $driverMatches = [];
        foreach ($sopirs as $nama => $sopir) {
            $driverMatches[$nama] = ['input_name' => $nama, 'matched' => true, 'sopir' => $sopir, 'confidence' => 100];
        }
        $routeMatches = [
            'Paket candirejo nganjuk' => ['input_route' => 'Paket candirejo nganjuk', 'matched' => true, 'tujuan' => $tujuan, 'confidence' => 100],
        ];

        $parsed = [
            'date' => '2026-11-04',
            'packages' => [[
                'route_name' => 'Paket candirejo nganjuk',
                'drivers' => ['Gun', 'Toni', 'Didik'],
                'lembur_drivers' => ['Gun', 'Toni'],
            ]],
            'lembur_unmatched' => [],
        ];

        $result = (new RitaseCreator())->create($parsed, $periode->id, $driverMatches, $routeMatches);

        // 3 base ritase, not 3 + 2 lembur duplicates.
        $this->assertSame(3, $result['created']);
        $this->assertSame(3, Ritase::count());
        $this->assertFalse(Sopir::where('nama', 'lembur')->exists());
        $this->assertSame(1, Tujuan::count());

        $gun = Ritase::where('kode_sopir', $sopirs['Gun']->kode_sopir)->first();
        $toni = Ritase::where('kode_sopir', $sopirs['Toni']->kode_sopir)->first();
        $didik = Ritase::where('kode_sopir', $sopirs['Didik']->kode_sopir)->first();

        $this->assertTrue((bool) $gun->is_lembur);
        $this->assertTrue((bool) $toni->is_lembur);
        $this->assertFalse((bool) $didik->is_lembur);
        // Parser never sets the nominal; penggajian does.
        $this->assertSame(0.0, (float) $gun->upah_lembur);
        $this->assertSame(0.0, (float) $toni->upah_lembur);
    }

    public function test_lembur_without_base_ritase_creates_nothing(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'PER-TEST',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
        $tujuan = Tujuan::create(['nama' => 'Paket candirejo nganjuk', 'status' => 'aktif']);
        $gun = Sopir::create(['nama' => 'Gun', 'status' => 'aktif']);
        $toni = Sopir::create(['nama' => 'Toni', 'status' => 'aktif']);

        $driverMatches = [
            'Gun' => ['input_name' => 'Gun', 'matched' => true, 'sopir' => $gun, 'confidence' => 100],
            'Toni' => ['input_name' => 'Toni', 'matched' => true, 'sopir' => $toni, 'confidence' => 100],
        ];
        $routeMatches = [
            'Paket candirejo nganjuk' => ['input_route' => 'Paket candirejo nganjuk', 'matched' => true, 'tujuan' => $tujuan, 'confidence' => 100],
        ];

        // Toni is only in the lembur list, not in the base package.
        $parsed = [
            'date' => '2026-11-04',
            'packages' => [[
                'route_name' => 'Paket candirejo nganjuk',
                'drivers' => ['Gun'],
                'lembur_drivers' => ['Toni'],
            ]],
            'lembur_unmatched' => [],
        ];

        (new RitaseCreator())->create($parsed, $periode->id, $driverMatches, $routeMatches);

        $this->assertSame(1, Ritase::count());
        $this->assertNull(Ritase::where('kode_sopir', $toni->kode_sopir)->first());
    }
}
