<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiKeyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $keys = $customer->apiKeys()
            ->withCount('usageLogs')
            ->latest()
            ->get()
            ->map(function ($k) {
                $k->status = $k->is_active ? 'active' : 'inactive';
                $k->rate_limit_rpm = $k->rate_limit_per_minute;
                return $k;
            });

        return ApiResponse::success($keys);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::error('Hanya akun pelanggan yang dapat mengelola API Key.', null, 403);
        }

        $activeSub = $customer->activeSubscription()->first();
        if (!$activeSub || $activeSub->status !== 'active') {
            return ApiResponse::error('Akses API memerlukan paket langganan aktif. Silakan pilih dan aktifkan paket langganan Anda terlebih dahulu.', null, 403);
        }


        $validator = Validator::make($request->all(), [

            'name' => ['required', 'string', 'max:100'],
            'ip_whitelist' => ['nullable'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:10', 'max:5000'],
            'rate_limit_rpm' => ['nullable', 'integer', 'min:10', 'max:5000'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $rateLimit = (int) ($request->input('rate_limit_per_minute') ?? $request->input('rate_limit_rpm', 60));
        $ipWhitelist = $request->input('ip_whitelist');
        if (is_array($ipWhitelist)) {
            $ipWhitelist = implode(',', array_filter(array_map('trim', $ipWhitelist)));
        }

        $pair = ApiKey::generate(
            customer: $customer,
            name: $request->input('name'),
            rateLimit: $rateLimit,
            ipWhitelist: $ipWhitelist
        );

        AuditLog::record(
            action: 'api_key.created',
            entity: 'ApiKey',
            entityId: (string) $pair['api_key']->id,
            newValues: ['name' => $pair['api_key']->name, 'prefix' => $pair['api_key']->key_prefix]
        );

        return ApiResponse::success([
            'api_key' => $pair['plain_key'],
            'api_secret' => $pair['plain_secret'],
            'plain_key' => $pair['plain_key'],
            'plain_secret' => $pair['plain_secret'],
            'key_record' => $pair['api_key'],
            'warning' => 'Please save your API key and secret now. The secret will NEVER be displayed again.',
        ], 'API Key generated successfully', [], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $key = $customer->apiKeys()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'ip_whitelist' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $key->update($validator->validated());

        return ApiResponse::success($key, 'API Key updated successfully');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $key = $customer->apiKeys()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        $key->delete();

        AuditLog::record(
            action: 'api_key.revoked',
            entity: 'ApiKey',
            entityId: (string) $key->id
        );

        return ApiResponse::success(null, 'API Key revoked successfully');
    }
}
