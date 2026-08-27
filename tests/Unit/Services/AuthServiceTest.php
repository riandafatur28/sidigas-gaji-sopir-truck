<?php

namespace Tests\Unit\Services;

use App\Models\Otp;
use App\Models\User;
use App\Services\AuthService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuthService();
    }

    public function test_send_otp_creates_otp_record(): void
    {
        Mail::fake();

        $this->service->sendOtp('test@example.com');

        $this->assertDatabaseHas('otps', [
            'email' => 'test@example.com',
        ]);

        $otp = Otp::where('email', 'test@example.com')->first();
        $this->assertNotNull($otp);
        $this->assertEquals(6, strlen($otp->otp));
        $this->assertTrue($otp->expires_at->isFuture());
    }

    public function test_send_otp_deletes_previous_otp(): void
    {
        Mail::fake();

        Otp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->service->sendOtp('test@example.com');

        $this->assertEquals(1, Otp::where('email', 'test@example.com')->count());
    }

    public function test_verify_otp_returns_true_for_valid_otp(): void
    {
        $otp = Otp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $result = $this->service->verifyOtp('test@example.com', '123456');

        $this->assertTrue($result);
        $this->assertDatabaseMissing('otps', ['id' => $otp->id]);
    }

    public function test_verify_otp_returns_false_for_expired_otp(): void
    {
        Otp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => Carbon::now()->subMinutes(1),
        ]);

        $result = $this->service->verifyOtp('test@example.com', '123456');

        $this->assertFalse($result);
    }

    public function test_verify_otp_returns_false_for_wrong_otp(): void
    {
        Otp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $result = $this->service->verifyOtp('test@example.com', '999999');

        $this->assertFalse($result);
    }

    public function test_verify_otp_deletes_otp_after_success(): void
    {
        $otp = Otp::create([
            'email' => 'test@example.com',
            'otp' => '123456',
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);

        $this->service->verifyOtp('test@example.com', '123456');

        $this->assertDatabaseMissing('otps', ['id' => $otp->id]);
    }

    public function test_reset_password_updates_hashed_password(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('old-password'),
        ]);

        $this->service->resetPassword('test@example.com', 'new-password');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password', $user->password));
        $this->assertFalse(Hash::check('old-password', $user->password));
    }
}
