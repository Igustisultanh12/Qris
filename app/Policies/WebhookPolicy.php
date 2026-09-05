<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Webhook;

class WebhookPolicy
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

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->customer_id === $webhook->customer_id;
    }

    public function create(User $user): bool
    {
        return $user->customer_id !== null;
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->customer_id === $webhook->customer_id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->customer_id === $webhook->customer_id;
    }
}
