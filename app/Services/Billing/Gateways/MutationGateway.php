<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MutationGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'mutation';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $paymentNumber = 'PAY-MUT-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_gateway' => 'mutation',
            'gateway_reference' => $paymentNumber,
            'payment_method' => $options['method'] ?? 'qris_dynamic',
            'amount' => $invoice->total,
            'fee' => 0,
            'status' => 'pending',
            'payload' => [
                'type' => 'qris_mutation',
                'target_amount' => $invoice->total,
                'note' => 'Automatic mutation detection from bank / e-wallet notification',
            ],
        ]);
    }

    public function verifyCallback(Request $request): PaymentCallbackResult
    {
        // Support Cekmutasi, Moota, or generic JSON mutation payload
        // Accepts: amount, nominal, total, credit, or nested in data array
        $data = $request->all();
        $amount = (int) ($data['amount'] ?? $data['nominal'] ?? $data['total'] ?? $data['credit'] ?? 0);
        
        // If wrapped inside a 'content' or 'data' array (like Moota/Cekmutasi webhook)
        if ($amount === 0 && isset($data['data'][0])) {
            $first = $data['data'][0];
            $amount = (int) ($first['amount'] ?? $first['nominal'] ?? $first['credit'] ?? 0);
        }

        $invoiceNumber = $data['invoice_number'] ?? $data['ref'] ?? $data['reference'] ?? '';

        return new PaymentCallbackResult(
            isValid: true,
            gatewayReference: $invoiceNumber ?: 'MUTATION-' . time(),
            invoiceNumber: $invoiceNumber,
            amount: $amount,
            status: 'success',
            paymentMethod: 'qris_dynamic',
            rawPayload: $data
        );
    }

    public function handleCallback(Request $request): Payment
    {
        $result = $this->verifyCallback($request);

        // 1. Find invoice by invoice_number if provided
        $invoice = null;
        if (!empty($result->invoiceNumber)) {
            $invoice = Invoice::where('invoice_number', $result->invoiceNumber)
                ->whereIn('status', ['pending', 'overdue'])
                ->first();
        }

        // 2. If no invoice number, match by exact amount for pending invoices
        if (!$invoice && $result->amount > 0) {
            $invoice = Invoice::where('total', $result->amount)
                ->whereIn('status', ['pending', 'overdue'])
                ->latest()
                ->first();
        }

        if (!$invoice) {
            throw new \InvalidArgumentException("No matching pending invoice found for amount: Rp {$result->amount}");
        }

        // Find existing pending payment or create new one
        $payment = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            $payment = $this->createPayment($invoice);
        }

        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
            'payload' => array_merge($payment->payload ?? [], [
                'mutation_callback' => $result->rawPayload,
                'detected_amount' => $result->amount,
            ]),
        ]);

        return $payment;
    }
}
