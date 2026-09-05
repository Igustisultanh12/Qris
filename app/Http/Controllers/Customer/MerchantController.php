<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Merchant;
use App\Models\MerchantQris;
use App\Services\Qris\Contracts\QrisConverterInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MerchantController extends Controller
{
    public function __construct(
        protected QrisConverterInterface $converter
    ) {}

    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $merchants = $customer->merchants()
            ->with(['primaryQris'])
            ->withCount('transactions')
            ->latest()
            ->get();

        return ApiResponse::success($merchants);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        // Check merchant quota
        if ($customer->merchants()->count() >= $customer->max_merchants) {
            return ApiResponse::error(
                "Merchant limit of {$customer->max_merchants} reached for your subscription tier. Please upgrade your plan.",
                null,
                403
            );
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'qris_static' => ['required', 'string', 'min:20'],
            'fee_mode' => ['nullable', 'in:absorbed,charged_to_customer'],
            'custom_fee_type' => ['nullable', 'in:none,fixed,percentage'],
            'custom_fee_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();
        $qrisString = trim($validated['qris_static']);

        // Validate QRIS payload
        $validation = $this->converter->validate($qrisString);
        if (!$validation->valid) {
            return ApiResponse::error('Invalid QRIS static payload', $validation->errors, 422);
        }

        $parsed = $this->converter->parse($qrisString);
        if ($parsed->method === 'dynamic') {
            return ApiResponse::error('The provided QRIS is already dynamic. Please provide a static merchant QRIS.', null, 422);
        }

        $merchantCode = 'MC-' . strtoupper(Str::random(8));

        $merchant = Merchant::create([
            'customer_id' => $customer->id,
            'merchant_code' => $merchantCode,
            'name' => $validated['name'],
            'store_name' => ($validated['store_name'] ?? null) ?: $parsed->merchantName,
            'address' => $validated['address'] ?? null,
            'city' => ($validated['city'] ?? null) ?: $parsed->merchantCity,
            'postal_code' => ($validated['postal_code'] ?? null) ?: $parsed->postalCode,
            'mcc' => $parsed->merchantCategoryCode,
            'status' => 'active',
            'fee_mode' => $validated['fee_mode'] ?? 'charged_to_customer',
            'custom_fee_type' => $validated['custom_fee_type'] ?? 'none',
            'custom_fee_value' => $validated['custom_fee_value'] ?? 0,
        ]);

        $merchantQris = MerchantQris::create([
            'merchant_id' => $merchant->id,
            'customer_id' => $customer->id,
            'qris_static' => $qrisString,
            'qris_version' => $parsed->version,
            'merchant_name_qris' => $parsed->merchantName,
            'merchant_city_qris' => $parsed->merchantCity,
            'postal_code' => $parsed->postalCode,
            'currency' => $parsed->currency,
            'mcc' => $parsed->merchantCategoryCode,
            'is_primary' => true,
            'is_active' => true,
            'metadata' => [
                'country_code' => $parsed->countryCode,
                'accounts' => array_map(fn ($a) => $a->toArray(), $parsed->merchantAccountInfo),
            ],
        ]);

        AuditLog::record(
            action: 'merchant.created',
            entity: 'Merchant',
            entityId: (string) $merchant->id,
            newValues: ['merchant_code' => $merchantCode, 'name' => $merchant->name]
        );

        $merchant->load('primaryQris');

        return ApiResponse::success($merchant, 'Merchant and static QRIS created successfully', [], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $merchant = $customer->merchants()
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->with(['primaryQris', 'transactions' => fn ($q) => $q->latest()->take(10)])
            ->withCount('transactions')
            ->firstOrFail();

        return ApiResponse::success($merchant);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $merchant = $customer->merchants()
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'store_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'fee_mode' => ['sometimes', 'in:absorbed,charged_to_customer'],
            'custom_fee_type' => ['sometimes', 'in:none,fixed,percentage'],
            'custom_fee_value' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $merchant->update($validator->validated());

        return ApiResponse::success($merchant, 'Merchant updated successfully');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $merchant = $customer->merchants()
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->firstOrFail();

        $merchant->delete();

        return ApiResponse::success(null, 'Merchant deleted successfully');
    }
}
