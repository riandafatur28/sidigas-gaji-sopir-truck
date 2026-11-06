<?php

namespace Tests\Unit\Services;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Services\LaporanBuilderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class LaporanBuilderServiceLemburTest extends TestCase
{
    use RefreshDatabase;

    private LaporanBuilderService $service;
    private Periode $periode;
    private Tujuan $tujuan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(LaporanBuilderService::class);

        $this->periode = Periode::create([
            'nama_periode' => 'PER-TEST',
            'tanggal_mulai' => '2026-11-01',
            'tanggal_selesai' => '2026-11-30',
            'status' => 'aktif',
        ]);
        $this->tujuan = Tujuan::create(['nama' => 'Paket candirejo nganjuk', 'status' => 'aktif']);

        // 2 rit valid di hari/tujuan yang sama: 1 lembur (100rb), 1 biasa.
        foreach (['Gun' => true, 'Didik' => false] as $nama => $lembur) {
            $sopir = Sopir::create(['nama' => $nama, 'status' => 'aktif']);
            Ritase::create([
                'periode_id' => $this->periode->id,
                'kode_sopir' => $sopir->kode_sopir,
                'kode_tujuan' => $this->tujuan->kode_tujuan,
                'tanggal' => '2026-11-04',
                'waktu' => 'pagi',
                'kabupaten' => 'Nganjuk',
                'status' => 'valid',
                'dt' => 330000,
                'upah_sopir' => 0,
                'is_lembur' => $lembur,
                'upah_lembur' => $lembur ? 100000 : 0,
            ]);
        }
    }

    public function test_laporan_memasukkan_lembur_ke_grand_total(): void
    {
        $data = $this->service->build(new Request(['periode' => $this->periode->id]))['data'];

        $this->assertSame(660000.0, $data['total_dt_all']);
        $this->assertSame(100000.0, $data['total_lembur_all']);
        // Solar/upah 0 di test ini: grand total = DT + lembur.
        $this->assertSame(760000.0, $data['grand_total_all']);

        $lemburRows = array_values(array_filter(
            $data['detail_rows'],
            fn ($r) => $r['jenis'] === 'Lembur'
        ));
        $this->assertCount(1, $lemburRows);
        $this->assertSame(100000.0, (float) $lemburRows[0]['harga']);
        $this->assertSame(1, $lemburRows[0]['qty']);
        $this->assertSame(100000.0, (float) $lemburRows[0]['total']);

        $subtotalRows = array_values(array_filter(
            $data['detail_rows'],
            fn ($r) => ($r['is_subtotal'] ?? false) === true
        ));
        $this->assertCount(1, $subtotalRows);
        $this->assertSame(760000.0, (float) $subtotalRows[0]['total']);
    }

    public function test_laporan_tanpa_lembur_tidak_menampilkan_baris_lembur(): void
    {
        Ritase::where('periode_id', $this->periode->id)->update(['is_lembur' => false, 'upah_lembur' => 0]);

        $data = $this->service->build(new Request(['periode' => $this->periode->id]))['data'];

        $this->assertSame(0.0, $data['total_lembur_all']);
        $this->assertSame(660000.0, $data['grand_total_all']);

        $lemburRows = array_values(array_filter(
            $data['detail_rows'],
            fn ($r) => $r['jenis'] === 'Lembur'
        ));
        $this->assertCount(0, $lemburRows);
    }
}
