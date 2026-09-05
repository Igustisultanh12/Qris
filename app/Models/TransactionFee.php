<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'fee_type',
        'fee_rate',
        'fee_amount',
        'fee_mode',
        'platform_cut',
        'merchant_net',
    ];

    protected function casts(): array
    {
        return [
            'fee_rate' => 'decimal:4',
            'fee_amount' => 'integer',
            'platform_cut' => 'integer',
            'merchant_net' => 'integer',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
