<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
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

    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->customer_id === $apiKey->customer_id;
    }

    public function create(User $user): bool
    {
        return $user->customer_id !== null;
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->customer_id === $apiKey->customer_id;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->customer_id === $apiKey->customer_id;
    }
}
