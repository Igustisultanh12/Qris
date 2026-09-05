<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Billing\DTOs\PaymentCallbackResult;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    public function getName(): string;

    /**
     * Create payment session or transaction in gateway.
     */
    public function createPayment(Invoice $invoice, array $options = []): Payment;

    /**
     * Verify incoming webhook/callback signature.
     */
    public function verifyCallback(Request $request): PaymentCallbackResult;

    /**
     * Handle verified callback and update payment state.
     */
    public function handleCallback(Request $request): Payment;
}
