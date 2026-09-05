<?php

namespace App\Traits;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCustomer
{
    /**
     * Boot the trait to scope queries to customer if authenticated user is a customer.
     */
    protected static function bootBelongsToCustomer(): void
    {
        static::creating(function ($model) {
            if (empty($model->customer_id) && auth()->check() && auth()->user()->customer_id) {
                $model->customer_id = auth()->user()->customer_id;
            }
        });

        static::addGlobalScope('customer_scope', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                // If user is not super-admin, restrict to their customer_id
                if (!$user->isSuperAdmin() && $user->customer_id) {
                    $builder->where($builder->getModel()->getTable() . '.customer_id', $user->customer_id);
                }
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
