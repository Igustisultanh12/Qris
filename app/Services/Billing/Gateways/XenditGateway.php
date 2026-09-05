<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class XenditGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'xendit';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $paymentNumber = 'XEN-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_gateway' => 'xendit',
            'gateway_reference' => $paymentNumber,
            'payment_method' => $options['method'] ?? 'invoice',
            'amount' => $invoice->total,
            'fee' => 0,
            'status' => 'pending',
            'payload' => [
                'external_id' => $paymentNumber,
                'invoice_url' => "https://checkout.xendit.co/web/{$paymentNumber}",
            ],
        ]);
    }

    public function verifyCallback(Request $request): PaymentCallbackResult
    {
        $callbackToken = Setting::get('xendit_callback_token', config('services.xendit.callback_token', ''));
        $incomingToken = $request->header('x-callback-token', '');

        $isValid = empty($callbackToken) || hash_equals($callbackToken, $incomingToken);

        $status = match (strtolower((string) $request->input('status'))) {
            'paid', 'settled' => 'success',
            'expired' => 'expired',
            default => 'failed',
        };

        return new PaymentCallbackResult(
            isValid: $isValid,
            gatewayReference: (string) $request->input('external_id', ''),
            invoiceNumber: (string) $request->input('external_id', ''),
            amount: (int) $request->input('paid_amount', $request->input('amount', 0)),
            status: $status,
            paymentMethod: $request->input('payment_method'),
            rawPayload: $request->all(),
            errorMessage: $isValid ? null : 'Xendit callback token mismatch'
        );
    }

    public function handleCallback(Request $request): Payment
    {
        $result = $this->verifyCallback($request);
        if (!$result->isValid) {
            throw new \InvalidArgumentException($result->errorMessage ?? 'Invalid callback token');
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
