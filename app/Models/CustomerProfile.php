<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'logo_path',
        'website',
        'webhook_url',
        'webhook_secret',
        'notification_preferences',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'notification_preferences' => 'array',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
