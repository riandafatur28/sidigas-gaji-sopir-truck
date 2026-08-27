<?php

namespace Tests\Feature;

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
            'email' => 'admin@test.com',
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

    public function test_update_modifies_tujuan(): void
    {
        $tujuan = \App\Models\Tujuan::create(['nama' => 'Jombang']);

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
        $tujuan = \App\Models\Tujuan::create(['nama' => 'Jombang']);

        $this->delete("/tujuan/{$tujuan->id}")->assertRedirect();

        $this->assertDatabaseMissing('tujuans', ['id' => $tujuan->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        \App\Models\Tujuan::create(['nama' => 'Jombang']);
        \App\Models\Tujuan::create(['nama' => 'Kediri']);

        $this->get('/tujuan?search=Jombang')->assertOk()->assertSee('Jombang');
    }
}
