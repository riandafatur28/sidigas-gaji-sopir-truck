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

class RitaseCrudTest extends TestCase
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
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->get('/ritase/table');
        $response->assertRedirect();
    }

    public function test_index_displays_ritase(): void
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
            'dt' => 330000,
        ]);

        $this->actingAs($this->user);
        $response = $this->get('/ritase/table?periode=' . $periode->id);
        $response->assertStatus(200);
        $response->assertSee('Budi');
    }

    public function test_store_creates_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);

        $this->actingAs($this->user);
        $response = $this->post('/ritase/table', [
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-15',
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
        ]);

        $response->assertRedirect();
    }

    public function test_store_validates_required_fields(): void
    {
        $this->actingAs($this->user);
        $response = $this->post('/ritase/table', []);
        $response->assertSessionHasErrors(['periode_id', 'kode_sopir', 'kode_tujuan', 'tanggal', 'waktu', 'kabupaten', 'status']);
    }

    public function test_update_modifies_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);

        $ritase = Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-15',
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $this->actingAs($this->user);
        $response = $this->put("/ritase/table/{$ritase->id}", [
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-16',
            'waktu' => 'malam',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
        ]);

        $response->assertRedirect();
    }

    public function test_destroy_deletes_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);

        $ritase = Ritase::create([
            'periode_id' => $periode->id,
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-15',
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
            'dt' => 330000,
        ]);

        $this->actingAs($this->user);
        $response = $this->delete("/ritase/table/{$ritase->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('ritases', ['id' => $ritase->id]);
    }

    public function test_cek_aturan_dt(): void
    {
        $sopir = Sopir::create(['nama' => 'Budi', 'status' => 'aktif']);
        $tujuan = Tujuan::create(['nama' => 'Nganjuk', 'status' => 'aktif']);

        $this->actingAs($this->user);
        $response = $this->post('/ritase/cek-aturan', [
            'kode_sopir' => $sopir->kode_sopir,
            'kode_tujuan' => $tujuan->kode_tujuan,
            'tanggal' => '2026-07-15',
            'waktu' => 'pagi',
            'kabupaten' => 'Nganjuk',
            'status' => 'valid',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['sewa_dt', 'keterangan']);
    }

    public function test_parser_form(): void
    {
        $this->actingAs($this->user);
        $response = $this->get('/ritase');
        $response->assertStatus(200);
    }
}
