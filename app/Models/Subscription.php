<?php

namespace App\Models;

use App\Traits\BelongsToCustomer;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory, HasUuid, BelongsToCustomer;

    protected $fillable = [
        'uuid',
        'customer_id',
        'plan_id',
        'status',
        'price',
        'currency',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'cancelled_at',
        'auto_renew',
        'grace_period_days',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'grace_period_days' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trial']) &&
            ($this->ends_at === null || $this->ends_at->isFuture() || $this->isInGracePeriod());
    }

    public function isInGracePeriod(): bool
    {
        if (!$this->ends_at || $this->ends_at->isFuture()) {
            return false;
        }

        $graceExpiry = $this->ends_at->copy()->addDays($this->grace_period_days);
        return now()->lessThanOrEqualTo($graceExpiry);
    }
}
