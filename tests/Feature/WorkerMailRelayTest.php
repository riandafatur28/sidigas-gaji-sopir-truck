<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WorkerMailRelayTest extends TestCase
{
    use RefreshDatabase;

    private function relayConfig(): void
    {
        config(['mail.relay_url' => 'https://relay.example.test/send']);
        config(['mail.relay_secret' => 'test-secret']);
    }

    private function gmailUser(): User
    {
        return User::create([
            'name' => 'User Tes',
            'email' => 'user@gmail.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    public function test_send_otp_posts_rendered_email_to_relay(): void
    {
        $this->relayConfig();
        $this->gmailUser();

        Http::fake(['https://relay.example.test/*' => Http::response(['id' => 'x'], 200)]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect(route('verify.otp.form', ['email' => 'user@gmail.com']))
            ->assertSessionHas('success');

        $this->assertSame(1, Otp::where('email', 'user@gmail.com')->count());

        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://relay.example.test/send') {
                return false;
            }
            if (($request->header('X-Mail-Secret')[0] ?? null) !== 'test-secret') {
                return false;
            }
            $data = $request->data();
            $otp = Otp::where('email', 'user@gmail.com')->first()->otp;

            return $data['to'] === 'user@gmail.com'
                && $data['subject'] === 'Kode OTP Reset Password - Sistem Armada'
                && str_contains($data['html'], $otp);
        });
    }

    public function test_relay_failure_shows_error_flash_not_500(): void
    {
        $this->relayConfig();
        $this->gmailUser();

        Http::fake(['https://relay.example.test/*' => Http::response(['message' => 'error'], 500)]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_missing_relay_config_shows_error_flash_not_500(): void
    {
        config(['mail.relay_url' => null, 'mail.relay_secret' => null]);
        $this->gmailUser();

        Http::fake();

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNothingSent();
    }
}
