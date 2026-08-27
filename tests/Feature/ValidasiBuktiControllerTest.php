<?php

namespace Tests\Feature;

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
}
