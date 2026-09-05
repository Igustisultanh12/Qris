<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer;

    protected $fillable = [
        'uuid',
        'webhook_id',
        'customer_id',
        'event',
        'payload',
        'response_status',
        'response_headers',
        'response_body',
        'duration_ms',
        'attempt',
        'max_attempts',
        'is_success',
        'error_message',
        'next_retry_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'response_headers' => 'array',
            'is_success' => 'boolean',
            'attempt' => 'integer',
            'max_attempts' => 'integer',
            'duration_ms' => 'integer',
            'next_retry_at' => 'datetime',
        ];
    }

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
