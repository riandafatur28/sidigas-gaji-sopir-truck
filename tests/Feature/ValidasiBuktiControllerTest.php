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

class ValidasiBuktiControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->user);
    }

    public function test_kelola_returns_ok(): void
    {
        $this->get('/validasi-bukti/kelola')->assertOk();
    }

    public function test_setujui_updates_status(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $this->post("/validasi-bukti/{$item->id}/setujui", [
            'catatan_mitra' => 'OK',
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals('disetujui', $item->status);
    }

    public function test_tolak_updates_status(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $this->post("/validasi-bukti/{$item->id}/tolak", [
            'catatan_mitra' => 'Foto tidak jelas',
        ])->assertRedirect();

        $item->refresh();
        $this->assertEquals('ditolak', $item->status);
    }

    public function test_detail_returns_ok(): void
    {
        $item = ValidasiBukti::create([
            'nama_sopir' => 'Budi',
            'nama_tujuan' => 'Jombang',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'pending',
        ]);

        $this->get("/validasi-bukti/{$item->id}")->assertOk();
    }

    public function test_tambah_ritase_menebak_kabupaten_dan_menolak_kirim_ulang(): void
    {
        Periode::create(['nama_periode' => 'Juli 2026', 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-07-31', 'status' => 'aktif']);
        $sopir = Sopir::create(['nama' => 'Budi']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk Kota']);

        $item = ValidasiBukti::create([
            'kode_sopir' => $sopir->kode_sopir,
            'nama_sopir' => 'Budi',
            'kode_tujuan' => $tujuan->kode_tujuan,
            'nama_tujuan' => 'Nganjuk Kota',
            'foto' => 'bukti/test.jpg',
            'waktu_foto' => now(),
            'tanggal' => '2026-07-22',
            'status' => 'disetujui',
        ]);

        $payload = [
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
        ];

        $this->post("/validasi-bukti/{$item->id}/ritase", $payload)
            ->assertRedirect(route('validasi-bukti.kelola'));

        $item->refresh();
        $this->assertNotNull($item->kode_ritase);
        $this->assertDatabaseHas('ritases', [
            'kode_ritase' => $item->kode_ritase,
            'kabupaten' => 'Nganjuk',
        ]);

        // Kirim ulang dari detail tidak boleh membuat ritase kedua.
        $this->post("/validasi-bukti/{$item->id}/ritase", $payload)->assertSessionHas('error');
        $this->assertSame(1, Ritase::count());
    }
}
