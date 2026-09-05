<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiUsageLog extends Model
{
    use HasFactory, BelongsToCustomer;

    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'customer_id',
        'api_key_id',
        'endpoint',
        'method',
        'ip_address',
        'user_agent',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'duration_ms',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_body' => 'array',
            'duration_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
