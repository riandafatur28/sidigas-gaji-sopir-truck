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

class PeriodeControllerTest extends TestCase
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

    public function test_index_requires_auth(): void
    {
        $this->actingAs(User::create([
            'name' => 'Guest',
            'email' => 'guest@gmail.com',
            'password' => bcrypt('password'),
        ]));
        $this->assertAuthenticated();
        $response = $this->get('/periode');
        $response->assertStatus(200);
    }

    public function test_store_creates_periode(): void
    {
        $response = $this->post('/periode', [
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('periodes', ['nama_periode' => 'Juli 2026']);
    }

    public function test_store_rejects_overlapping_periode(): void
    {
        Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $response = $this->post('/periode', [
            'nama_periode' => 'Agustus 2026',
            'tanggal_mulai' => '2026-07-15',
            'tanggal_selesai' => '2026-08-15',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_update_modifies_periode(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $response = $this->put("/periode/{$periode->id}", [
            'nama_periode' => 'Juli 2026 Updated',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'selesai',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('periodes', ['id' => $periode->id, 'status' => 'selesai']);
    }

    public function test_destroy_removes_periode_with_no_ritase(): void
    {
        $periode = Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        $response = $this->delete("/periode/{$periode->id}");
        $response->assertRedirect();
        $this->assertDatabaseMissing('periodes', ['id' => $periode->id]);
    }

    public function test_destroy_blocks_periode_with_ritase(): void
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

        $response = $this->delete("/periode/{$periode->id}");
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('periodes', ['id' => $periode->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        Periode::create([
            'nama_periode' => 'Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'aktif',
        ]);

        Periode::create([
            'nama_periode' => 'Agustus 2026',
            'tanggal_mulai' => '2026-08-01',
            'tanggal_selesai' => '2026-08-31',
            'status' => 'selesai',
        ]);

        $response = $this->get('/periode?search=Juli');
        $response->assertStatus(200);
        $response->assertSee('Juli 2026');
    }

    public function test_store_requires_validation(): void
    {
        $response = $this->post('/periode', []);
        $response->assertSessionHasErrors(['nama_periode', 'tanggal_mulai', 'tanggal_selesai']);
    }
}
