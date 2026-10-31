<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WorkerMailService
{
    public function sendOtp(string $email, string $otp): void
    {
        $url = (string) config('mail.relay_url');
        $secret = (string) config('mail.relay_secret');

        if ($url === '' || $secret === '') {
            throw new RuntimeException('Konfigurasi relay email belum lengkap.');
        }

        $mailable = (new OtpMail($otp, $email))->build();
        $subject = $mailable->subject ?? 'Kode OTP Reset Password - Sistem Armada';

        $response = Http::timeout(20)
            ->withHeaders(['X-Mail-Secret' => $secret])
            ->post($url, [
                'to' => $email,
                'subject' => $subject,
                'html' => $mailable->render(),
            ]);

        if (! $response->successful()) {
            Log::warning('Relay email gagal', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException('Email gagal terkirim (relay: ' . $response->status() . ').');
        }
    }
}
