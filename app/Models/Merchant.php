<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer, SoftDeletes;

    protected $fillable = [
        'uuid',
        'customer_id',
        'merchant_code',
        'name',
        'store_name',
        'address',
        'city',
        'postal_code',
        'mcc',
        'acquirer_name',
        'status',
        'fee_mode',
        'custom_fee_type',
        'custom_fee_value',
    ];

    protected function casts(): array
    {
        return [
            'custom_fee_value' => 'decimal:2',
        ];
    }

    public function qrisList(): HasMany
    {
        return $this->hasMany(MerchantQris::class);
    }

    public function primaryQris(): HasOne
    {
        return $this->hasOne(MerchantQris::class)->where('is_primary', true);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
