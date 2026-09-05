<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionReceiptMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Transaction $transaction
    ) {}

    public function build(): self
    {
        return $this->subject("Konfirmasi Pembayaran QRIS [{$this->transaction->reference}]")
            ->view('emails.transaction')
            ->with([
                'transaction' => $this->transaction,
            ]);
    }
}
