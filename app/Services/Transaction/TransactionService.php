<?php

namespace App\Services\Transaction;

use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionFee;
use App\Services\Qris\Contracts\QrisConverterInterface;
use App\Services\Qris\DTOs\FeeData;
use App\Services\Qris\QrisGenerator;
use App\Jobs\DispatchWebhookJob;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    public function __construct(
        protected QrisConverterInterface $converter
    ) {}

    /**
     * Create a dynamic QRIS transaction.
     */
    public function createDynamicTransaction(
        Customer $customer,
        Merchant $merchant,
        int $amount,
        string $reference,
        ?FeeData $customFee = null,
        ?ApiKey $apiKey = null,
        ?string $idempotencyKey = null,
        ?int $expiryMinutes = null,
        string $source = 'api',
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): Transaction {
        // Idempotency check (Requirement #16)
        if (!empty($idempotencyKey)) {
            $existing = Transaction::where('customer_id', $customer->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        // Verify merchant belongs to customer
        if ($merchant->customer_id !== $customer->id) {
            throw ValidationException::withMessages([
                'merchant_id' => ['The specified merchant does not belong to this customer.'],
            ]);
        }

        if ($merchant->status !== 'active') {
            throw ValidationException::withMessages([
                'merchant_id' => ['Merchant is not active.'],
            ]);
        }

        // Get merchant's primary active QRIS
        $primaryQris = $merchant->primaryQris ?? $merchant->qrisList()->where('is_active', true)->first();
        if (!$primaryQris) {
            throw ValidationException::withMessages([
                'merchant_id' => ['Merchant does not have an active QRIS configured.'],
            ]);
        }

        // Determine fee
        $feeData = $customFee;
        if (!$feeData && $merchant->custom_fee_type !== 'none' && $merchant->custom_fee_value > 0) {
            $feeData = new FeeData(
                type: $merchant->custom_fee_type,
                value: (float) $merchant->custom_fee_value,
                mode: $merchant->fee_mode
            );
        }

        // Execute QRIS Static -> Dynamic conversion
        $conversionResult = $this->converter->convert($primaryQris->qris_static, $amount, $feeData);
        if (!$conversionResult->success) {
            throw ValidationException::withMessages([
                'qris' => $conversionResult->errors,
            ]);
        }

        // Expiry calculation (default from settings or 15 mins)
        $defaultExpiry = (int) Setting::get('qris_default_expiry_minutes', 15);
        $expiryMinutes = $expiryMinutes ?: $defaultExpiry;
        $expiresAt = now()->addMinutes($expiryMinutes);

        // Generate QR Code SVG and store in storage/app/public/qris
        $svgContent = QrisGenerator::generateSvg($conversionResult->dynamicPayload);
        $qrFilename = 'qris/' . date('Y/m') . '/' . uniqid('qr_') . '.svg';
        Storage::disk('public')->put($qrFilename, $svgContent);

        // Fee calculations
        $feeAmount = $conversionResult->fee;
        $total = $conversionResult->total;
        $feeMode = $feeData ? $feeData->mode : 'charged_to_customer';

        $transaction = Transaction::create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'api_key_id' => $apiKey?->id,
            'reference' => $reference,
            'amount' => $amount,
            'fee' => $feeAmount,
            'total' => $total,
            'fee_mode' => $feeMode,
            'qris_static' => $primaryQris->qris_static,
            'qris_dynamic' => $conversionResult->dynamicPayload,
            'qr_image_path' => $qrFilename,
            'status' => 'generated', // Requirement #21: initial status is generated, not fake paid
            'source' => $source,
            'ip_address' => $ipAddress,
            'user_agent' => substr((string) $userAgent, 0, 500),
            'idempotency_key' => $idempotencyKey,
            'expires_at' => $expiresAt,
            'metadata' => [
                'merchant_name' => $merchant->name,
                'merchant_code' => $merchant->merchant_code,
                'conversion_crc' => $conversionResult->crc,
            ],
        ]);

        // Record fee details
        if ($feeAmount > 0 && $feeData) {
            TransactionFee::create([
                'transaction_id' => $transaction->id,
                'fee_type' => $feeData->type,
                'fee_rate' => $feeData->type === 'percentage' ? $feeData->value : 0,
                'fee_amount' => $feeAmount,
                'fee_mode' => $feeMode,
                'platform_cut' => $feeAmount,
                'merchant_net' => $feeMode === 'absorbed' ? ($amount - $feeAmount) : $amount,
            ]);
        }

        // Trigger webhook delivery in background
        DispatchWebhookJob::dispatch($customer, 'transaction.generated', [
            'transaction_id' => $transaction->transaction_number,
            'uuid' => $transaction->uuid,
            'merchant_code' => $merchant->merchant_code,
            'reference' => $reference,
            'amount' => $amount,
            'fee' => $feeAmount,
            'total' => $total,
            'status' => 'generated',
            'expires_at' => $expiresAt->toIso8601String(),
            'created_at' => $transaction->created_at->toIso8601String(),
        ]);

        return $transaction;
    }

    /**
     * Mark a dynamic transaction as paid and trigger webhooks and email receipts.
     */
    public function markAsPaid(Transaction $transaction, ?string $paymentRef = null, array $metadata = []): Transaction
    {
        if ($transaction->status === 'paid') {
            return $transaction;
        }

        $transaction->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metadata' => array_merge($transaction->metadata ?? [], $metadata, [
                'payment_reference' => $paymentRef,
                'paid_at' => now()->toIso8601String(),
            ]),
        ]);

        // 1. Dispatch Webhook
        DispatchWebhookJob::dispatch($transaction->customer, 'transaction.paid', [
            'transaction_id' => $transaction->transaction_number,
            'uuid' => $transaction->uuid,
            'reference' => $transaction->reference,
            'amount' => $transaction->amount,
            'fee' => $transaction->fee,
            'total' => $transaction->total,
            'status' => 'paid',
            'paid_at' => $transaction->paid_at->toIso8601String(),
        ]);

        // 2. Dispatch Email Receipt via Email Gateway
        try {
            app(\App\Services\Mail\EmailGatewayService::class)->sendTransactionPaidEmail($transaction);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return $transaction;
    }
}
