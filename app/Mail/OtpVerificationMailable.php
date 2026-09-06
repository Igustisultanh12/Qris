<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpVerificationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $otp
    ) {}

    public function build(): self
    {
        return $this->subject('Kode Verifikasi OTP Akun Qmis - PT Kreatif Sky Abadi')
            ->view('emails.otp_verification')
            ->with([
                'user' => $this->user,
                'otp' => $this->otp,
            ]);
    }
}
