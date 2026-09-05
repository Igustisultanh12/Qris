<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ManualTransferGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'manual';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $paymentNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_gateway' => 'manual',
            'gateway_reference' => $paymentNumber,
            'payment_method' => $options['method'] ?? 'bank_transfer',
            'amount' => $invoice->total,
            'fee' => 0,
            'status' => 'pending',
            'payload' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT KREATIF ABADI',
                'instructions' => 'Transfer exact amount to PT Kreatif Abadi bank account and confirm via dashboard.',
            ],
        ]);
    }

    public function verifyCallback(Request $request): PaymentCallbackResult
    {
        $paymentNumber = $request->input('payment_number');
        $amount = (int) $request->input('amount');
        $invoiceNumber = $request->input('invoice_number', '');

        return new PaymentCallbackResult(
            isValid: true,
            gatewayReference: $paymentNumber ?? 'MANUAL',
            invoiceNumber: $invoiceNumber,
            amount: $amount,
            status: 'success',
            paymentMethod: 'bank_transfer',
            rawPayload: $request->all()
        );
    }

    public function handleCallback(Request $request): Payment
    {
        $result = $this->verifyCallback($request);
        $payment = Payment::where('payment_number', $result->gatewayReference)->firstOrFail();

        $payment->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        return $payment;
    }
}
