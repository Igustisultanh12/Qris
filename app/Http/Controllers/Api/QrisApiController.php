<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CreateDynamicQrisRequest;
use App\Http\Requests\Api\ParseQrisRequest;
use App\Http\Requests\Api\ValidateQrisRequest;
use App\Http\Resources\TransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\ApiKey;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Services\Qris\Contracts\QrisConverterInterface;
use App\Services\Qris\DTOs\FeeData;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QrisApiController extends Controller
{
    public function __construct(
        protected QrisConverterInterface $converter,
        protected TransactionService $transactionService
    ) {}

    /**
     * Parse raw QRIS payload and extract structured details.
     */
    public function parse(ParseQrisRequest $request): JsonResponse
    {
        $qrisData = $this->converter->parse($request->validated('qris'));

        return ApiResponse::success($qrisData->toArray(), 'QRIS payload parsed successfully');
    }

    /**
     * Validate QRIS payload integrity, CRC, mandatory fields, and formatting.
     */
    public function validateQris(ValidateQrisRequest $request): JsonResponse
    {
        $result = $this->converter->validate($request->validated('qris'));

        if (!$result->valid) {
            return ApiResponse::error('QRIS validation failed', $result->errors, 422);
        }

        return ApiResponse::success($result->toArray(), 'QRIS is valid');
    }

    /**
     * Create Dynamic QRIS from merchant static QRIS with nominal amount and fees.
     */
    public function createDynamic(CreateDynamicQrisRequest $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');
        /** @var ApiKey|null $apiKey */
        $apiKey = $request->attributes->get('api_key');

        $validated = $request->validated();

        // Resolve merchant by UUID or merchant_code
        $merchant = Merchant::where('customer_id', $customer->id)
            ->where(function ($q) use ($validated) {
                $q->where('uuid', $validated['merchant_id'])
                  ->orWhere('merchant_code', $validated['merchant_id']);
            })
            ->first();

        if (!$merchant) {
            return ApiResponse::error('Merchant not found', ['merchant_id' => ['Merchant not found']], 404);
        }

        // Custom fee if provided
        $customFee = null;
        if (!empty($validated['fee_type']) && $validated['fee_type'] !== 'none') {
            $customFee = new FeeData(
                type: $validated['fee_type'],
                value: (float) ($validated['fee_value'] ?? 0),
                mode: $validated['fee_mode'] ?? 'charged_to_customer'
            );
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        try {
            $transaction = $this->transactionService->createDynamicTransaction(
                customer: $customer,
                merchant: $merchant,
                amount: $validated['amount'],
                reference: $validated['reference'],
                customFee: $customFee,
                apiKey: $apiKey,
                idempotencyKey: $idempotencyKey,
                expiryMinutes: $validated['expiry_minutes'] ?? null,
                source: 'api',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return ApiResponse::success(
                new TransactionResource($transaction),
                'Dynamic QRIS created successfully',
                [],
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Failed to create dynamic QRIS', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Internal server error generating QRIS: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get QRIS details by Transaction ID or UUID.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where(function ($q) use ($id) {
                $q->where('transaction_number', $id)
                  ->orWhere('uuid', $id);
            })
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        return ApiResponse::success(new TransactionResource($transaction));
    }

    /**
     * Cancel an unpaid dynamic QRIS.
     */
    public function cancel(Request $request, string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where(function ($q) use ($id) {
                $q->where('transaction_number', $id)
                  ->orWhere('uuid', $id);
            })
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        if (!in_array($transaction->status, ['pending', 'generated'], true)) {
            return ApiResponse::error("Cannot cancel transaction with status '{$transaction->status}'", null, 422);
        }

        $transaction->update(['status' => 'cancelled']);

        return ApiResponse::success(new TransactionResource($transaction), 'Transaction cancelled successfully');
    }

    /**
     * Simulate payment for an unpaid dynamic QRIS transaction (for sandbox/integration testing).
     * Automatically updates transaction to PAID, dispatches 'transaction.paid' webhook with HMAC-SHA256,
     * and triggers email receipt.
     */
    public function simulatePaid(Request $request, string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where(function ($q) use ($id) {
                $q->where('transaction_number', $id)
                  ->orWhere('uuid', $id)
                  ->orWhere('reference', $id);
            })
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        if ($transaction->status === 'paid') {
            return ApiResponse::success(new TransactionResource($transaction), 'Transaction is already paid');
        }

        if ($transaction->status === 'cancelled' || $transaction->status === 'expired') {
            return ApiResponse::error("Cannot simulate payment for transaction with status '{$transaction->status}'", null, 422);
        }

        $paymentRef = 'SIM-PAID-' . strtoupper(Str::random(10));
        $transaction = $this->transactionService->markAsPaid(
            $transaction,
            $paymentRef,
            [
                'simulated' => true,
                'simulated_at' => now()->toIso8601String(),
                'ip_address' => $request->ip(),
            ]
        );

        return ApiResponse::success(
            new TransactionResource($transaction),
            'Payment successfully simulated. Transaction status changed to PAID and webhook event transaction.paid has been dispatched.'
        );
    }
}
