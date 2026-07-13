<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $email;

    public function __construct(string $otp, string $email)
    {
        $this->otp = $otp;
        $this->email = $email;
    }

    public function build(): self
    {
        return $this->subject('Kode OTP Reset Password - Sistem Armada')
                    ->html("
                        <div style='font-family: Arial, sans-serif; padding: 20px; background: #f9fafb; border-left: 4px solid #FFC107;'>
                            <h2 style='color: #111827; margin-bottom: 10px;'>Reset Password</h2>
                            <p style='color: #374151; font-size: 14px;'>Halo,</p>
                            <p style='color: #374151; font-size: 14px;'>Gunakan kode OTP berikut untuk mereset password Anda:</p>
                            <h1 style='background: #FFC107; color: #111827; padding: 20px; text-align: center; letter-spacing: 8px; border-radius: 8px; font-size: 32px; margin: 20px 0;'>{$this->otp}</h1>
                            <p style='color: #6b7280; font-size: 12px;'>Kode ini berlaku selama <strong>15 menit</strong>. Jangan bagikan kepada siapapun.</p>
                            <hr style='border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;'>
                            <p style='color: #9ca3af; font-size: 11px;'>Email ini dikirim otomatis oleh Sistem Armada Dump-Truck. Jika Anda tidak meminta reset password, abaikan email ini.</p>
                        </div>
                    ");
    }
}
