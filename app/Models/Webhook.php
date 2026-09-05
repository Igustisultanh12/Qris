<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Webhook extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer;

    protected $fillable = [
        'uuid',
        'customer_id',
        'url',
        'secret',
        'events',
        'is_active',
        'failure_count',
        'last_triggered_at',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'failure_count' => 'integer',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function signPayload(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
