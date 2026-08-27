<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Ritase;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\Periode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SopirControllerTest extends TestCase
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
        $this->get('/sopir')->assertOk();
    }

    public function test_store_creates_sopir(): void
    {
        $this->post('/sopir', ['nama' => 'Budi Santoso'])
            ->assertRedirect();

        $this->assertDatabaseHas('sopirs', ['nama' => 'Budi Santoso']);
    }

    public function test_store_requires_name(): void
    {
        $this->post('/sopir', [])->assertSessionHasErrors('nama');
    }

    public function test_update_modifies_sopir(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);

        $this->put("/sopir/{$sopir->id}", [
            'nama' => 'Budi Santoso',
            'status' => 'nonaktif',
        ])->assertRedirect();

        $sopir->refresh();
        $this->assertEquals('Budi Santoso', $sopir->nama);
        $this->assertEquals('nonaktif', $sopir->status);
    }

    public function test_destroy_removes_sopir(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi']);

        $this->delete("/sopir/{$sopir->id}")->assertRedirect();

        $this->assertDatabaseMissing('sopirs', ['id' => $sopir->id]);
    }

    public function test_destroy_blocks_sopir_with_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi']);
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

        $this->delete("/sopir/{$sopir->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('sopirs', ['id' => $sopir->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        Sopir::create(['nama' => 'Budi Santoso']);
        Sopir::create(['nama' => 'Agus Wijaya']);

        $this->get('/sopir?search=Budi')->assertOk()->assertSee('Budi Santoso');
    }
}
