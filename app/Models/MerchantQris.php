<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MerchantQris extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer;

    protected $table = 'merchant_qris';

    protected $fillable = [
        'uuid',
        'merchant_id',
        'customer_id',
        'qris_static',
        'qris_version',
        'merchant_name_qris',
        'merchant_city_qris',
        'postal_code',
        'currency',
        'mcc',
        'nmid',
        'acquirer',
        'is_primary',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }
}
