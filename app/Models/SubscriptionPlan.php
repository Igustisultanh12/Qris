<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price',
        'billing_cycle',
        'max_merchants',
        'max_api_calls_per_month',
        'max_transactions_per_month',
        'max_storage_mb',
        'rate_limit_per_minute',
        'features',
        'is_active',
        'is_popular',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'features' => 'array',
            'is_active' => 'boolean',
            'is_popular' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }
}
