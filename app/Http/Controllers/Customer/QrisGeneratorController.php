<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Merchant;
use App\Services\Qris\Contracts\QrisConverterInterface;
use App\Services\Qris\DTOs\FeeData;
use App\Services\Qris\QrisGenerator;
use App\Services\Transaction\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class QrisGeneratorController extends Controller
{
    public function __construct(
        protected QrisConverterInterface $converter,
        protected TransactionService $transactionService
    ) {}

    /**
     * Validate an arbitrary QRIS string (e.g. from camera scan or image upload).
     */
    public function validateStatic(Request $request): JsonResponse
    {
        $qrisString = trim((string) $request->input('qris', ''));
        $validation = $this->converter->validate($qrisString);

        if (!$validation->valid) {
            return ApiResponse::error('Invalid QRIS payload', $validation->errors, 422);
        }

        $parsed = $this->converter->parse($qrisString);

        return ApiResponse::success([
            'valid' => true,
            'data' => $parsed->toArray(),
        ], 'QRIS static valid');
    }

    /**
     * Generate dynamic QRIS from the Customer UI wizard.
     */
    public function generate(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $validator = Validator::make($request->all(), [
            'merchant_id' => ['required'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'reference' => ['required', 'string', 'max:100'],
            'fee_type' => ['nullable', 'in:none,fixed,percentage'],
            'fee_value' => ['nullable', 'numeric', 'min:0'],
            'fee_mode' => ['nullable', 'in:absorbed,charged_to_customer'],
            'expiry_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        $merchant = $customer->merchants()
            ->where(fn ($q) => $q->where('uuid', $validated['merchant_id'])
                ->orWhere('id', $validated['merchant_id'])
                ->orWhere('merchant_code', $validated['merchant_id']))
            ->first();

        if (!$merchant) {
            return ApiResponse::error('Merchant not found', null, 404);
        }

        $customFee = null;
        if (!empty($validated['fee_type']) && $validated['fee_type'] !== 'none' && $validated['fee_value'] > 0) {
            $customFee = new FeeData(
                type: $validated['fee_type'],
                value: (float) $validated['fee_value'],
                mode: $validated['fee_mode'] ?? 'charged_to_customer'
            );
        }

        try {
            $transaction = $this->transactionService->createDynamicTransaction(
                customer: $customer,
                merchant: $merchant,
                amount: $validated['amount'],
                reference: $validated['reference'],
                customFee: $customFee,
                expiryMinutes: $validated['expiry_minutes'] ?? 15,
                source: 'web',
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            $svgString = QrisGenerator::generateSvg($transaction->qris_dynamic);
            $pngDataUri = QrisGenerator::generatePngDataUri($transaction->qris_dynamic);

            $data = (new TransactionResource($transaction))->resolve();
            $data['svg_raw'] = $svgString;
            $data['qr_base64'] = $pngDataUri;

            return ApiResponse::success($data, 'Dynamic QRIS generated successfully', [], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponse::error('Failed to generate QRIS', $e->errors(), 422);
        } catch (\Throwable $e) {
            return ApiResponse::error('Server error: ' . $e->getMessage(), null, 500);
        }
    }
}
