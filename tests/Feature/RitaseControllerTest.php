<?php

namespace Tests\Feature;

use App\Models\Periode;
use App\Models\Sopir;
use App\Models\Tujuan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RitaseControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Periode $periode;
    private Sopir $sopir;
    private Tujuan $tujuan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->user);

        $this->periode = Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);
        $this->sopir = Sopir::create(['nama' => 'Budi']);
        $this->tujuan = Tujuan::create(['nama' => 'Jombang']);
    }

    public function test_index_returns_ok(): void
    {
        $this->get('/ritase/table')->assertOk();
    }

    public function test_store_creates_ritase(): void
    {
        $this->post('/ritase/table', [
            'periode_id' => $this->periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 0,
            'nominal_kompensasi' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('ritases', [
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
        ]);
    }

    public function test_update_modifies_ritase(): void
    {
        $ritase = \App\Models\Ritase::create([
            'periode_id' => $this->periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'pending',
            'dt' => 0,
        ]);

        $this->put("/ritase/table/{$ritase->id}", [
            'periode_id' => $this->periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
            'nominal_kompensasi' => 0,
        ])->assertRedirect();

        $ritase->refresh();
        $this->assertEquals('valid', $ritase->status);
    }

    public function test_destroy_removes_ritase(): void
    {
        $ritase = \App\Models\Ritase::create([
            'periode_id' => $this->periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 0,
        ]);

        $this->delete("/ritase/table/{$ritase->id}")->assertRedirect();

        $this->assertDatabaseMissing('ritases', ['id' => $ritase->id]);
    }

    public function test_api_get_ritase_data_returns_json(): void
    {
        \App\Models\Ritase::create([
            'periode_id' => $this->periode->id,
            'kode_sopir' => $this->sopir->kode_sopir,
            'kode_tujuan' => $this->tujuan->kode_tujuan,
            'tanggal' => '2026-07-22',
            'waktu' => 'pagi',
            'kabupaten' => 'Jombang',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $res = $this->getJson('/api/get-ritase-data?periode=' . $this->periode->id);
        $res->assertOk();
        $res->assertJsonStructure(['sopir']);
    }
}
