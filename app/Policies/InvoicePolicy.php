<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
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

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->customer_id === $invoice->customer_id;
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $user->customer_id === $invoice->customer_id && in_array($invoice->status, ['pending', 'overdue']);
    }
}
