<?php

namespace App\Services\Billing\DTOs;

class PaymentCallbackResult
{
    public function __construct(
        public bool $isValid,
        public string $gatewayReference,
        public string $invoiceNumber,
        public int $amount,
        public string $status, // 'success', 'pending', 'failed', 'expired'
        public ?string $paymentMethod = null,
        public array $rawPayload = [],
        public ?string $errorMessage = null
    ) {}
}
