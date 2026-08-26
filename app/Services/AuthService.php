<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    public function sendOtp(string $email): void
    {
        Otp::where('email', $email)->delete();
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Otp::create([
            'email' => $email,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(15),
        ]);
        Mail::to($email)->send(new OtpMail($otp, $email));
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
