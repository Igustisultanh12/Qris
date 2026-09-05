<?php

namespace App\Services\Billing;

use App\Jobs\DispatchWebhookJob;
use App\Models\AuditLog;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Str;

class BillingService
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager
    ) {}

    /**
     * Generate an invoice for a customer subscription.
     */
    public function createSubscriptionInvoice(
        Customer $customer,
        SubscriptionPlan $plan,
        ?Coupon $coupon = null,
        int $billingPeriodMonths = 1
    ): Invoice {
        $subtotal = $plan->price * $billingPeriodMonths;
        $discount = 0;

        if ($coupon && $coupon->isValidFor($customer, $subtotal)) {
            $discount = $coupon->calculateDiscount($subtotal);
        }

        $taxRate = (float) Setting::get('billing_tax_percent', 11.00);
        $taxEnabled = (bool) Setting::get('billing_tax_enabled', true);
        $taxableAmount = max(0, $subtotal - $discount);
        $taxAmount = $taxEnabled ? (int) round(($taxableAmount * $taxRate) / 100) : 0;
        $fee = 0;
        $total = $taxableAmount + $taxAmount + $fee;

        $invoicePrefix = Setting::get('invoice_prefix', 'INV');
        $dateStr = now()->format('Ym');
        $random = strtoupper(Str::random(6));
        $invoiceNumber = "{$invoicePrefix}-{$dateStr}-{$random}";

        // Find or create customer subscription record
        $subscription = $customer->subscriptions()->where('plan_id', $plan->id)->first();
        if (!$subscription) {
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'status' => 'pending',
                'price' => $plan->price,
                'starts_at' => now(),
                'ends_at' => now()->addMonths($billingPeriodMonths),
                'auto_renew' => true,
            ]);
        }

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'customer_id' => $customer->id,
            'subscription_id' => $subscription->id,
            'billing_period_start' => now(),
            'billing_period_end' => now()->addMonths($billingPeriodMonths),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'fee' => $fee,
            'total' => $total,
            'status' => 'pending',
            'due_date' => now()->addDays((int) Setting::get('invoice_due_days', 3)),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Paket {$plan->name} ({$plan->billing_cycle})",
            'quantity' => $billingPeriodMonths,
            'unit_price' => $plan->price,
            'total_price' => $subtotal,
        ]);

        if ($coupon && $discount > 0) {
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'discount_amount' => $discount,
            ]);
            $coupon->increment('uses_count');
        }

        DispatchWebhookJob::dispatch($customer, 'invoice.created', [
            'invoice_id' => $invoice->invoice_number,
            'uuid' => $invoice->uuid,
            'total' => $invoice->total,
            'due_date' => $invoice->due_date?->toDateString(),
            'status' => $invoice->status,
        ]);

        return $invoice;
    }

    /**
     * Mark an invoice as successfully paid and renew/activate the associated subscription.
     */
    public function markInvoicePaid(Invoice $invoice, Payment $payment): void
    {
        if ($invoice->status === 'paid') {
            return;
        }

        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
            'payment_method' => $payment->payment_method,
        ]);

        // Activate or extend subscription
        $subscription = $invoice->subscription;
        if ($subscription) {
            $plan = $subscription->plan;
            $monthsToAdd = match ($plan?->billing_cycle) {
                'quarterly' => 3,
                'yearly' => 12,
                default => 1,
            };

            $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
                ? $subscription->ends_at
                : now();

            $subscription->update([
                'status' => 'active',
                'starts_at' => $subscription->starts_at ?: now(),
                'ends_at' => $baseDate->copy()->addMonths($monthsToAdd),
            ]);

            // Update customer limits from plan
            if ($plan) {
                $invoice->customer->update([
                    'max_merchants' => $plan->max_merchants,
                ]);
            }

            DispatchWebhookJob::dispatch($invoice->customer, 'subscription.renewed', [
                'subscription_id' => $subscription->uuid,
                'plan' => $plan?->name,
                'status' => 'active',
                'ends_at' => $subscription->ends_at->toIso8601String(),
            ]);
        }

        AuditLog::record(
            action: 'invoice.paid',
            entity: 'Invoice',
            entityId: (string) $invoice->id,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'paid', 'payment_id' => $payment->id]
        );

        DispatchWebhookJob::dispatch($invoice->customer, 'invoice.paid', [
            'invoice_id' => $invoice->invoice_number,
            'uuid' => $invoice->uuid,
            'total' => $invoice->total,
            'paid_at' => now()->toIso8601String(),
        ]);
    }
}
