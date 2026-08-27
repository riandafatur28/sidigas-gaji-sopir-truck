<?php

namespace Tests\Feature;

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
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);
        $this->actingAs($this->user);
    }

    public function test_index_returns_ok(): void
    {
        $this->get('/periode')->assertOk();
    }

    public function test_store_creates_periode(): void
    {
        $this->post('/periode', [
            'nama_periode' => 'Periode Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ])->assertRedirect();

        $this->assertDatabaseHas('periodes', ['nama_periode' => 'Periode Juli 2026']);
    }

    public function test_update_modifies_periode(): void
    {
        $periode = \App\Models\Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $this->put("/periode/{$periode->id}", [
            'nama_periode' => 'Periode Juli 2026',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
            'status' => 'selesai',
        ])->assertRedirect();

        $periode->refresh();
        $this->assertEquals('Periode Juli 2026', $periode->nama_periode);
        $this->assertEquals('selesai', $periode->status);
    }

    public function test_destroy_removes_periode_with_no_ritase(): void
    {
        $periode = \App\Models\Periode::create([
            'nama_periode' => 'P1',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $this->delete("/periode/{$periode->id}")->assertRedirect();

        $this->assertDatabaseMissing('periodes', ['id' => $periode->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        \App\Models\Periode::create([
            'nama_periode' => 'Periode Juli',
            'tanggal_mulai' => '2026-07-01',
            'tanggal_selesai' => '2026-07-31',
        ]);

        $this->get('/periode?search=Juli')->assertOk()->assertSee('Periode Juli');
    }
}
