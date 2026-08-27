<?php

namespace Tests\Feature;

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
            'email' => 'admin@test.com',
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

    public function test_update_modifies_sopir(): void
    {
        $sopir = \App\Models\Sopir::create(['nama' => 'Budi']);

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
        $sopir = \App\Models\Sopir::create(['nama' => 'Budi']);

        $this->delete("/sopir/{$sopir->id}")->assertRedirect();

        $this->assertDatabaseMissing('sopirs', ['id' => $sopir->id]);
    }

    public function test_search_filters_by_nama(): void
    {
        \App\Models\Sopir::create(['nama' => 'Budi Santoso']);
        \App\Models\Sopir::create(['nama' => 'Agus Wijaya']);

        $this->get('/sopir?search=Budi')->assertOk()->assertSee('Budi Santoso');
    }
}
