<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
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

    public function test_connection_failure_retries_then_succeeds(): void
    {
        $this->relayConfig();
        $this->gmailUser();

        $calls = 0;
        Http::fake(['https://relay.example.test/*' => function () use (&$calls) {
            $calls++;
            if ($calls === 1) {
                throw new ConnectionException('cURL error 35: SSL routines::wrong version number');
            }

            return Http::response(['id' => 'x'], 200);
        }]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect(route('verify.otp.form', ['email' => 'user@gmail.com']))
            ->assertSessionHas('success');

        $this->assertSame(1, Otp::where('email', 'user@gmail.com')->count());
        $this->assertSame(2, $calls);
    }

    public function test_connection_failure_after_retries_shows_error_flash_not_500(): void
    {
        $this->relayConfig();
        $this->gmailUser();

        $calls = 0;
        Http::fake(['https://relay.example.test/*' => function () use (&$calls) {
            $calls++;

            throw new ConnectionException('cURL error 35: SSL routines::wrong version number');
        }]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(3, $calls);
    }

    public function test_relay_with_proxy_options_configured_sends_successfully(): void
    {
        $this->relayConfig();
        config(['mail.relay_proxy' => 'http://proxy.example.test:3128']);
        config(['mail.relay_ca_bundle' => __FILE__]);
        $this->gmailUser();

        Http::fake(['https://relay.example.test/*' => Http::response(['id' => 'x'], 200)]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect(route('verify.otp.form', ['email' => 'user@gmail.com']))
            ->assertSessionHas('success');

        $this->assertSame(1, Otp::where('email', 'user@gmail.com')->count());
    }

    public function test_missing_ca_bundle_falls_back_to_system_ca(): void
    {
        $this->relayConfig();
        config(['mail.relay_proxy' => 'http://proxy.example.test:3128']);
        config(['mail.relay_ca_bundle' => '/nonexistent/ca-bundle.pem']);
        $this->gmailUser();

        Http::fake(['https://relay.example.test/*' => Http::response(['id' => 'x'], 200)]);

        $this->post('/forgot-password', ['email' => 'user@gmail.com'])
            ->assertRedirect(route('verify.otp.form', ['email' => 'user@gmail.com']))
            ->assertSessionHas('success');
    }

    public function test_forgot_password_page_shows_error_flash(): void
    {
        $this->withSession(['error' => 'Kode OTP gagal dikirim. Coba lagi sebentar, atau hubungi admin bila berlanjut.'])
            ->get('/forgot-password')
            ->assertOk()
            ->assertSee('Kode OTP gagal dikirim');
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
