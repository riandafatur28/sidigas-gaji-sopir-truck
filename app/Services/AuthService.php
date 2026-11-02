<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    public function sendOtp(string $email): void
    {
        Otp::where('email', $email)->delete();
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Otp::create([
            'user_id' => User::where('email', $email)->value('id'),
            'email' => $email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);
        app(WorkerMailService::class)->sendOtp($email, $otp);
    }

    public function verifyOtp(string $email, string $otp): bool
    {
        $otpData = Otp::where('email', $email)
            ->where('otp', $otp)
            ->where('expires_at', '>=', Carbon::now())
            ->first();

        if (!$otpData) return false;

        $otpData->delete();
        return true;
    }

    public function resetPassword(string $email, string $password): void
    {
        $user = User::where('email', $email)->firstOrFail();
        $user->password = Hash::make($password);
        $user->save();
    }

    public function getGoogleRedirectUrl(): string
    {
        $host = request()->getHost();
        if (str_contains($host, 'ngrok')) {
            return 'https://' . $host . '/auth/google/callback';
        }
        return env('GOOGLE_REDIRECT_URL', config('services.google.redirect', route('google.callback')));
    }
}
