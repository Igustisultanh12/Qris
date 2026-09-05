<?php

namespace App\Policies;

use App\Models\Merchant;
use App\Models\User;

class MerchantPolicy
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

    public function view(User $user, Merchant $merchant): bool
    {
        return $user->customer_id === $merchant->customer_id;
    }

    public function create(User $user): bool
    {
        if (!$user->customer_id) {
            return false;
        }

        // Check if customer has reached max_merchants limit
        $customer = $user->customer;
        if (!$customer) {
            return false;
        }

        $currentCount = $customer->merchants()->count();
        return $currentCount < $customer->max_merchants;
    }

    public function update(User $user, Merchant $merchant): bool
    {
        return $user->customer_id === $merchant->customer_id;
    }

    public function delete(User $user, Merchant $merchant): bool
    {
        return $user->customer_id === $merchant->customer_id;
    }
}
