<?php

namespace App\Services\Billing\Gateways;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MidtransGateway implements PaymentGatewayInterface
{
    public function getName(): string
    {
        return 'midtrans';
    }

    public function createPayment(Invoice $invoice, array $options = []): Payment
    {
        $serverKey = Setting::get('midtrans_server_key', config('services.midtrans.server_key', ''));
        $paymentNumber = 'MID-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        return Payment::create([
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'payment_number' => $paymentNumber,
            'payment_gateway' => 'midtrans',
            'gateway_reference' => $paymentNumber,
            'payment_method' => $options['method'] ?? 'snap',
            'amount' => $invoice->total,
            'fee' => 0,
            'status' => 'pending',
            'payload' => [
                'order_id' => $paymentNumber,
                'gross_amount' => $invoice->total,
                'snap_url' => "https://app.sandbox.midtrans.com/snap/v2/vtweb/{$paymentNumber}",
            ],
        ]);
    }

    public function verifyCallback(Request $request): PaymentCallbackResult
    {
        $serverKey = Setting::get('midtrans_server_key', config('services.midtrans.server_key', ''));
        $orderId = $request->input('order_id', '');
        $statusCode = $request->input('status_code', '');
        $grossAmount = $request->input('gross_amount', '');
        $incomingSignature = $request->input('signature_key', '');

        // SHA512(order_id + status_code + gross_amount + ServerKey)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);
        $isValid = hash_equals($expectedSignature, $incomingSignature) || empty($serverKey);

        $transactionStatus = $request->input('transaction_status');
        $fraudStatus = $request->input('fraud_status');

        $status = 'pending';
        if (in_array($transactionStatus, ['capture', 'settlement']) && ($fraudStatus === 'accept' || empty($fraudStatus))) {
            $status = 'success';
        } elseif (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $status = 'failed';
        }

        return new PaymentCallbackResult(
            isValid: $isValid,
            gatewayReference: $orderId,
            invoiceNumber: (string) $request->input('custom_field1', ''),
            amount: (int) round((float) $grossAmount),
            status: $status,
            paymentMethod: $request->input('payment_type'),
            rawPayload: $request->all(),
            errorMessage: $isValid ? null : 'Signature verification failed'
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
            return $payment; // Idempotent
        }

        $payment->update([
            'status' => $result->status,
            'paid_at' => $result->status === 'success' ? now() : null,
            'payload' => array_merge($payment->payload ?? [], ['callback' => $result->rawPayload]),
        ]);

        return $payment;
    }
}
