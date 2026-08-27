<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
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

    public function test_show_login_page(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Login');
    }

    public function test_login_with_valid_credentials(): void
    {
        $this->createUser();
        $response = $this->post('/login', [
            'email' => 'test@gmail.com',
            'password' => 'password123',
        ]);
        $response->assertRedirect('/dashboard');
    }

    public function test_login_with_invalid_credentials(): void
    {
        $this->createUser();
        $response = $this->post('/login', [
            'email' => 'test@gmail.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_gmail_domain(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@yahoo.com',
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_email(): void
    {
        $response = $this->post('/login', [
            'password' => 'password123',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@gmail.com',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_show_forgot_password_page(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
    }

    public function test_send_otp_with_valid_email(): void
    {
        $this->createUser();
        $response = $this->post('/forgot-password', [
            'email' => 'test@gmail.com',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('otps', ['email' => 'test@gmail.com']);
    }

    public function test_send_otp_with_nonexistent_email(): void
    {
        $response = $this->post('/forgot-password', [
            'email' => 'notfound@gmail.com',
        ]);
        $response->assertSessionHasErrors('email');
    }

    public function test_otp_replaces_old_otp(): void
    {
        $this->createUser();
        Otp::create([
            'email' => 'test@gmail.com',
            'otp' => '111111',
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->post('/forgot-password', ['email' => 'test@gmail.com']);

        $this->assertDatabaseMissing('otps', ['otp' => '111111']);
        $this->assertDatabaseHas('otps', ['email' => 'test@gmail.com']);
    }

    public function test_show_verify_otp_page(): void
    {
        $response = $this->get('/verify-otp?email=test@gmail.com');
        $response->assertStatus(200);
    }

    public function test_verify_otp_with_valid_code(): void
    {
        Otp::create([
            'email' => 'test@gmail.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@gmail.com',
            'otp' => '123456',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseMissing('otps', ['email' => 'test@gmail.com']);
    }

    public function test_verify_otp_with_invalid_code(): void
    {
        Otp::create([
            'email' => 'test@gmail.com',
            'otp' => '123456',
            'expires_at' => now()->addMinutes(15),
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@gmail.com',
            'otp' => '000000',
        ]);
        $response->assertSessionHasErrors('otp');
    }

    public function test_verify_otp_with_expired_code(): void
    {
        Otp::create([
            'email' => 'test@gmail.com',
            'otp' => '123456',
            'expires_at' => now()->subMinutes(20),
        ]);

        $response = $this->post('/verify-otp', [
            'email' => 'test@gmail.com',
            'otp' => '123456',
        ]);
        $response->assertSessionHasErrors('otp');
    }

    public function test_show_reset_password_page(): void
    {
        $response = $this->get('/reset-password?email=test@gmail.com');
        $response->assertStatus(200);
    }

    public function test_update_password(): void
    {
        $user = $this->createUser();
        $response = $this->post('/reset-password', [
            'email' => 'test@gmail.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);
        $response->assertRedirect('/login');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_update_password_requires_confirmation(): void
    {
        $this->createUser();
        $response = $this->post('/reset-password', [
            'email' => 'test@gmail.com',
            'password' => 'newpassword123',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_update_password_min_length(): void
    {
        $this->createUser();
        $response = $this->post('/reset-password', [
            'email' => 'test@gmail.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);
        $response->assertSessionHasErrors('password');
    }

    public function test_logout(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect();
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
    }
}
