<?php

namespace App\Contracts;

use App\Models\Transaction;

interface PaymentStatusProviderInterface
{
    /**
     * Check payment status against acquirer/provider API.
     * Returns true if confirmed paid, false otherwise.
     */
    public function checkStatus(Transaction $transaction): bool;
}
