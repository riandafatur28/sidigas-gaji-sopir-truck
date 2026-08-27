<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Periode;
use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TujuanControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->user);
    }

    public function test_index_returns_ok(): void
    {
        $this->get('/tujuan')->assertOk();
    }

    public function test_store_creates_tujuan(): void
    {
        $this->post('/tujuan', ['nama' => 'Jombang'])
            ->assertRedirect();

        $this->assertDatabaseHas('tujuans', ['nama' => 'Jombang']);
    }

    public function test_store_requires_name(): void
    {
        $this->post('/tujuan', [])->assertSessionHasErrors('nama');
    }

    public function test_update_modifies_tujuan(): void
    {
        $tujuan = Tujuan::create(['nama' => 'Jombang']);

        $this->put("/tujuan/{$tujuan->id}", [
            'nama' => 'Jombang Kota',
            'status' => 'nonaktif',
        ])->assertRedirect();

        $tujuan->refresh();
        $this->assertEquals('Jombang Kota', $tujuan->nama);
        $this->assertEquals('nonaktif', $tujuan->status);
    }

    public function test_destroy_removes_tujuan(): void
    {
        $tujuan = Tujuan::create(['nama' => 'Jombang']);

        $this->delete("/tujuan/{$tujuan->id}")->assertRedirect();

        $this->assertDatabaseMissing('tujuans', ['id' => $tujuan->id]);
    }

    public function test_destroy_blocks_tujuan_with_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);

        Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-15',
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
            'dt' => 0,
        ]);

        $this->delete("/tujuan/{$tujuan->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('tujuans', ['id' => $tujuan->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        Tujuan::create(['nama' => 'Jombang']);
        Tujuan::create(['nama' => 'Kediri']);

        $this->get('/tujuan?search=Jombang')->assertOk()->assertSee('Jombang');
    }
}
