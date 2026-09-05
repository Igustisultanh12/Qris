<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrisPayload extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'merchant_id',
        'merchant_qris_id',
        'raw_payload',
        'parsed_data',
        'crc',
        'is_valid',
        'validation_errors',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'is_valid' => 'boolean',
            'validation_errors' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function merchantQris(): BelongsTo
    {
        return $this->belongsTo(MerchantQris::class);
    }
}
