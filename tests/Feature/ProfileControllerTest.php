<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password' => bcrypt('password123'),
        ], $overrides));
    }

    public function test_show_profile_page(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->get('/profil');
        $response->assertStatus(200);
        $response->assertSee('Test User');
    }

    public function test_update_profile_name_and_email(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Updated Name',
            'email' => 'updated@gmail.com',
        ]);
        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['name' => 'Updated Name', 'email' => 'updated@gmail.com']);
    }

    public function test_update_profile_with_password(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password_lama' => 'password123',
            'password_baru' => 'newpass123',
            'password_baru_confirmation' => 'newpass123',
        ]);
        $response->assertRedirect();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_update_profile_with_wrong_current_password(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Test User',
            'email' => 'test@gmail.com',
            'password_lama' => 'wrongpassword',
            'password_baru' => 'newpass123',
            'password_baru_confirmation' => 'newpass123',
        ]);
        $response->assertSessionHasErrors('password_lama');
    }

    public function test_update_profile_requires_name(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->post('/profil', [
            'name' => '',
            'email' => 'test@gmail.com',
        ]);
        $response->assertSessionHasErrors('name');
    }

    public function test_update_profile_requires_valid_email(): void
    {
        $user = $this->createUser();
        $response = $this->actingAs($user)->post('/profil', [
            'name' => 'Test User',
            'email' => 'invalid-email',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_update_profile_unique_email(): void
    {
        $user1 = $this->createUser(['email' => 'user1@gmail.com']);
        $this->createUser(['email' => 'user2@gmail.com']);

        $response = $this->actingAs($user1)->post('/profil', [
            'name' => 'User 1',
            'email' => 'user2@gmail.com',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get('/profil');
        $response->assertRedirect();
    }
}
