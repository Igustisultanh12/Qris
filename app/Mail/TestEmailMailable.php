<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestEmailMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $recipientEmail,
        public string $mailerName,
        public string $host
    ) {}

    public function build(): self
    {
        return $this->subject('Tes Email Gateway - Qmis PT Kreatif Sky Abadi')
            ->view('emails.test')
            ->with([
                'recipient' => $this->recipientEmail,
                'mailer' => $this->mailerName,
                'host' => $this->host,
            ]);
    }
}
