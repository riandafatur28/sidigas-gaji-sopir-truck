<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
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
        $this->get('/dashboard')->assertOk();
    }

    public function test_index_with_filter_semua(): void
    {
        $this->get('/dashboard?periode=semua')->assertOk();
    }

    public function test_index_with_filter_bulan_ini(): void
    {
        $this->get('/dashboard?periode=bulan_ini')->assertOk();
    }
}
