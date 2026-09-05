<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Jobs\DispatchWebhookJob;
use App\Models\Webhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WebhookController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $webhooks = $customer->webhooks()
            ->with(['deliveries' => fn ($q) => $q->latest()->take(10)])
            ->latest()
            ->get();

        return ApiResponse::success($webhooks);
    }

    public function store(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:255'],
            'events' => ['nullable', 'array'],
            'events.*' => ['string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $secret = 'whsec_' . Str::random(32);
        $webhook = Webhook::create([
            'customer_id' => $customer->id,
            'url' => $request->input('url'),
            'secret' => $secret,
            'events' => $request->input('events') ?: ['*'],
            'is_active' => true,
        ]);

        return ApiResponse::success($webhook, 'Webhook endpoint configured successfully', [], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $webhook = $customer->webhooks()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        $validator = Validator::make($request->all(), [
            'url' => ['sometimes', 'required', 'url'],
            'events' => ['sometimes', 'array'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $webhook->update($validator->validated());

        return ApiResponse::success($webhook, 'Webhook updated successfully');
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $webhook = $customer->webhooks()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        $webhook->delete();

        return ApiResponse::success(null, 'Webhook deleted successfully');
    }

    public function test(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $webhook = $customer->webhooks()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        DispatchWebhookJob::dispatch($customer, 'test.ping', [
            'message' => 'This is a test webhook payload from Kreatif QRIS Platform.',
            'webhook_id' => $webhook->uuid,
            'timestamp' => now()->toIso8601String(),
        ]);

        return ApiResponse::success(null, 'Test webhook dispatched successfully');
    }
}
