<?php

namespace Tests\Feature;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\User;
use App\Models\ValidasiBukti;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValidasiSearchDeleteGajiSearchTest extends TestCase
{
    use RefreshDatabase;

    private Sopir $sopir;
    private Sopir $sopirLain;
    private Tujuan $tujuan;
    private Tujuan $tujuanLain;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($user);

        $this->sopir = Sopir::create(['nama' => 'Budi Santoso']);
        $this->sopirLain = Sopir::create(['nama' => 'Agus Wijaya']);
        $this->tujuan = Tujuan::create(['nama' => 'Jombang Kota']);
        $this->tujuanLain = Tujuan::create(['nama' => 'Blitar Ponggok']);
    }

    public function test_kelola_search_by_nama_sopir(): void
    {
        ValidasiBukti::create([
            'nama_sopir' => 'Budi Santoso', 'nama_tujuan' => 'Jombang Kota',
            'foto' => 'bukti/a.jpg', 'waktu_foto' => now(), 'tanggal' => '2026-07-22',
        ]);
        ValidasiBukti::create([
            'nama_sopir' => 'Agus Wijaya', 'nama_tujuan' => 'Blitar Ponggok',
            'foto' => 'bukti/b.jpg', 'waktu_foto' => now(), 'tanggal' => '2026-07-23',
        ]);

        $res = $this->get('/validasi-bukti/kelola?status=semua&search=' . urlencode('Agus'));
        $res->assertOk();
        $res->assertSee('Agus Wijaya');
        $res->assertDontSee('Budi Santoso');
    }

    public function test_kelola_search_by_tanggal(): void
    {
        ValidasiBukti::create([
            'nama_sopir' => 'Budi Santoso', 'nama_tujuan' => 'Jombang Kota',
            'foto' => 'bukti/a.jpg', 'waktu_foto' => now(), 'tanggal' => '2026-07-22',
        ]);

        // format DD/MM/YYYY harus ketemu tanggal 22/07/2026
        $res = $this->get('/validasi-bukti/kelola?status=semua&search=' . urlencode('22/07/2026'));
        $res->assertOk();
        $res->assertSee('Budi Santoso');
    }

    public function test_destroy_validasi_bukti(): void
    {
        $vb = ValidasiBukti::create([
            'nama_sopir' => 'Budi Santoso', 'nama_tujuan' => 'Jombang Kota',
            'foto' => 'bukti/a.jpg', 'waktu_foto' => now(), 'tanggal' => '2026-07-22',
        ]);

        $this->delete('/validasi-bukti/' . $vb->id)
            ->assertRedirect();
        $this->assertDatabaseMissing('validasi_bukti', ['id' => $vb->id]);
    }

    public function test_gaji_search_filters_by_sopir_name(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'P1', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-07-31',
        ]);

        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22', 'waktu' => 'pagi', 'kabupaten' => 'Jombang',
            'status' => 'valid', 'dt' => 330000,
        ]);
        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $this->sopirLain->kode_sopir,
            'kode_tujuan' => $this->tujuanLain->kode_tujuan,
            'tanggal' => '2026-07-22', 'waktu' => 'pagi', 'kabupaten' => 'Lainnya',
            'status' => 'valid', 'dt' => 330000,
        ]);

        $res = $this->getJson('/api/get-ritase-data?periode=' . $periode->id . '&search=' . urlencode('Agus'));
        $res->assertOk();
        $names = collect($res->json('sopir'))->pluck('nama_sopir')->all();
        $this->assertSame(['Agus Wijaya'], $names);
    }
}
