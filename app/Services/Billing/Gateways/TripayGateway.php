<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TripayGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'tripay';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $paymentNumber = 'TRI-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_gateway' => 'tripay',
            'gateway_reference' => $paymentNumber,
            'payment_method' => $options['method'] ?? 'qris',
            'amount' => $invoice->total,
            'fee' => 0,
            'status' => 'pending',
            'payload' => [
                'reference' => $paymentNumber,
                'checkout_url' => "https://tripay.co.id/checkout/{$paymentNumber}",
            ],
        ]);
    }

    public function verifyCallback(Request $request): PaymentCallbackResult
    {
        $privateKey = Setting::get('tripay_private_key', config('services.tripay.private_key', ''));
        $incomingSignature = $request->header('X-Callback-Signature', '');
        $json = $request->getContent();

        $expectedSignature = hash_hmac('sha256', $json, $privateKey);
        $isValid = empty($privateKey) || hash_equals($expectedSignature, $incomingSignature);

        $status = match (strtoupper((string) $request->input('status'))) {
            'PAID' => 'success',
            'EXPIRED' => 'expired',
            default => 'failed',
        };

        return new PaymentCallbackResult(
            isValid: $isValid,
            gatewayReference: (string) $request->input('merchant_ref', ''),
            invoiceNumber: (string) $request->input('merchant_ref', ''),
            amount: (int) $request->input('total_amount', 0),
            status: $status,
            paymentMethod: $request->input('payment_method'),
            rawPayload: $request->all(),
            errorMessage: $isValid ? null : 'Tripay callback signature mismatch'
        );
    }

    public function handleCallback(Request $request): Payment
    {
        $result = $this->verifyCallback($request);
        if (!$result->isValid) {
            throw new \InvalidArgumentException($result->errorMessage ?? 'Invalid callback signature');
        }

        $payment = Payment::where('gateway_reference', $result->gatewayReference)->firstOrFail();

        if ($payment->status === 'success') {
            return $payment;
        }

        $payment->update([
            'status' => $result->status,
            'paid_at' => $result->status === 'success' ? now() : null,
            'payload' => array_merge($payment->payload ?? [], ['callback' => $result->rawPayload]),
        ]);

        return $payment;
    }
}
