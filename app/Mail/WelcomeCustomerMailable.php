<?php

namespace App\Mail;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeCustomerMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Customer $customer
    ) {}

    public function build(): self
    {
        return $this->subject('Selamat Datang di Qmis - PT Kreatif Sky Abadi')
            ->view('emails.welcome')
            ->with([
                'user' => $this->user,
                'customer' => $this->customer,
            ]);
    }
}
