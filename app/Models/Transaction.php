<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer;

    protected $fillable = [
        'uuid',
        'transaction_number',
        'customer_id',
        'merchant_id',
        'api_key_id',
        'reference',
        'amount',
        'fee',
        'total',
        'fee_mode',
        'qris_static',
        'qris_dynamic',
        'qr_image_path',
        'status',
        'source',
        'ip_address',
        'user_agent',
        'idempotency_key',
        'expires_at',
        'paid_at',
        'metadata',
    ];

    protected $appends = [
        'fee_amount',
        'total_amount',
    ];

    public function getFeeAmountAttribute(): int
    {
        return (int) ($this->fee ?? 0);
    }

    public function getTotalAmountAttribute(): int
    {
        return (int) ($this->total ?? $this->amount ?? 0);
    }

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'fee' => 'integer',
            'total' => 'integer',
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
        });
    }

    public static function generateTransactionNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(8));
        return "TRX-{$date}-{$random}";
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function feeDetail(): HasOne
    {
        return $this->hasOne(TransactionFee::class);
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
