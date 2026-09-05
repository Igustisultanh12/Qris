<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'type',
        'value',
        'max_discount',
        'min_spend',
        'max_uses',
        'uses_count',
        'max_uses_per_customer',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'max_discount' => 'integer',
            'min_spend' => 'integer',
            'max_uses' => 'integer',
            'uses_count' => 'integer',
            'max_uses_per_customer' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValidFor(Customer $customer, int $subtotal): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        if ($subtotal < $this->min_spend) {
            return false;
        }

        $customerUses = $this->usages()->where('customer_id', $customer->id)->count();
        if ($customerUses >= $this->max_uses_per_customer) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->type === 'percentage') {
            $discount = (int) round(($subtotal * $this->value) / 100);
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
            return min($discount, $subtotal);
        }

        return min($this->value, $subtotal);
    }
}
