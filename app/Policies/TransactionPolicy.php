<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->customer_id !== null;
    }

    public function view(User $user, Transaction $transaction): bool
    {
        return $user->customer_id === $transaction->customer_id;
    }

    public function cancel(User $user, Transaction $transaction): bool
    {
        return $user->customer_id === $transaction->customer_id && in_array($transaction->status, ['pending', 'generated']);
    }
}
