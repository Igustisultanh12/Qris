<?php

namespace App\Services\Billing;

use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RefundService
{
    /**
     * Create a pending refund request for an invoice.
     */
    public function requestRefund(Invoice $invoice, string $reason, ?int $amount = null): Refund
    {
        if ($invoice->status !== 'paid') {
            throw new InvalidArgumentException('Only paid invoices can be refunded.');
        }

        $amount = $amount ?: $invoice->total;
        if ($amount > $invoice->total) {
            throw new InvalidArgumentException('Refund amount cannot exceed invoice total.');
        }

        $latestPayment = $invoice->latestPayment;
        $refundNumber = 'REF-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Refund::create([
            'refund_number' => $refundNumber,
            'invoice_id' => $invoice->id,
            'payment_id' => $latestPayment?->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $amount,
            'reason' => $reason,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve and process a refund (requires Super Admin user).
     */
    public function processRefund(Refund $refund, User $adminUser, ?string $notes = null): void
    {
        if (!$adminUser->isSuperAdmin()) {
            throw new InvalidArgumentException('Only super admins can approve and process refunds.');
        }

        if ($refund->status !== 'pending') {
            throw new InvalidArgumentException("Cannot process refund with status '{$refund->status}'.");
        }

        $refund->update([
            'status' => 'processed',
            'approved_by' => $adminUser->id,
            'processed_at' => now(),
            'notes' => $notes,
        ]);

        $refund->invoice->update([
            'status' => 'refunded',
        ]);

        AuditLog::record(
            action: 'refund.processed',
            entity: 'Refund',
            entityId: (string) $refund->id,
            oldValues: ['status' => 'pending'],
            newValues: ['status' => 'processed', 'approved_by' => $adminUser->id]
        );
    }
}
