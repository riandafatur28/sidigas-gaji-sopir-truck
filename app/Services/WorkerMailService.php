<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Http\Client\ConnectionException;
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

        $payload = [
            'to' => $email,
            'subject' => $subject,
            'html' => $mailable->render(),
        ];

        $options = $this->relayHttpOptions();

        // Gangguan sesaat di jalur egress (mis. cURL error 35 saat handshake TLS)
        // membuat request tidak pernah sampai ke Worker — aman dicoba ulang.
        // Hanya ConnectionException yang di-retry; respons HTTP error (4xx/5xx)
        // dan konfigurasi hilang langsung gagal tanpa retry agar tidak ada
        // email ganda dari percobaan yang sebenarnya sudah sampai.
        $attempts = 0;
        $maxAttempts = 3;

        while (true) {
            $attempts++;

            try {
                $response = Http::timeout(20)
                    ->withHeaders(['X-Mail-Secret' => $secret])
                    ->withOptions($options)
                    ->post($url, $payload);
            } catch (ConnectionException $e) {
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }

                Log::warning('Relay email gagal (koneksi), mencoba ulang', [
                    'attempt' => $attempts,
                    'error' => $e->getMessage(),
                ]);

                sleep(1);

                continue;
            }

            if (! $response->successful()) {
                Log::warning('Relay email gagal', [
                    'status' => $response->status(),
                    'body' => substr($response->body(), 0, 500),
                ]);

                throw new RuntimeException('Email gagal terkirim (relay: ' . $response->status() . ').');
            }

            return;
        }
    }

    /**
     * Opsi HTTP untuk request relay. Proxy + bundle CA dibaca dari config
     * (bukan env proses) karena env proses tidak diteruskan ke anak `php -S`.
     * Bila tidak dikonfigurasi, kembalikan array kosong (perilaku default:
     * koneksi langsung, CA sistem) untuk environment laptop.
     *
     * @return array{proxy?: string, verify?: string|bool}
     */
    private function relayHttpOptions(): array
    {
        $options = [];

        $proxy = (string) config('mail.relay_proxy');
        if ($proxy !== '') {
            $options['proxy'] = $proxy;
        }

        $caBundle = (string) config('mail.relay_ca_bundle');
        if ($caBundle !== '') {
            if (is_file($caBundle)) {
                $options['verify'] = $caBundle;
            } else {
                Log::warning('Relay CA bundle tidak ditemukan, memakai CA sistem', [
                    'path' => $caBundle,
                ]);
            }
        }

        return $options;
    }
}
