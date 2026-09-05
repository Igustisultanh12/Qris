<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminPlanController extends Controller
{
    public function index(): JsonResponse
    {
        $plans = SubscriptionPlan::withCount('subscriptions')->orderBy('sort_order')->get();
        return ApiResponse::success($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $input = $request->all();
        if (!isset($input['billing_cycle']) && isset($input['billing_interval'])) {
            $input['billing_cycle'] = $input['billing_interval'];
        }
        $input['billing_cycle'] = $input['billing_cycle'] ?? 'monthly';
        $input['max_api_calls_per_month'] = $input['max_api_calls_per_month'] ?? 10000;
        $input['max_transactions_per_month'] = $input['max_transactions_per_month'] ?? 5000;
        if (!isset($input['rate_limit_per_minute']) && isset($input['rate_limit_rpm'])) {
            $input['rate_limit_per_minute'] = $input['rate_limit_rpm'];
        }
        $input['rate_limit_per_minute'] = $input['rate_limit_per_minute'] ?? 60;

        $validator = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:0'],
            'billing_cycle' => ['required', 'in:monthly,quarterly,yearly'],
            'max_merchants' => ['required', 'integer', 'min:1'],
            'max_api_calls_per_month' => ['required', 'integer', 'min:100'],
            'max_transactions_per_month' => ['required', 'integer', 'min:100'],
            'rate_limit_per_minute' => ['required', 'integer', 'min:10'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();
        $slug = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($validated['name']);

        $plan = SubscriptionPlan::create(array_merge($validated, [
            'slug' => $slug,
            'is_active' => true,
        ]));

        AuditLog::record(
            action: 'plan.created',
            entity: 'SubscriptionPlan',
            entityId: (string) $plan->id,
            newValues: ['name' => $plan->name, 'price' => $plan->price]
        );

        return ApiResponse::success($plan, 'Subscription plan created', [], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $plan = SubscriptionPlan::findOrFail($id);

        $input = $request->all();
        if (isset($input['rate_limit_rpm']) && !isset($input['rate_limit_per_minute'])) {
            $input['rate_limit_per_minute'] = $input['rate_limit_rpm'];
        }
        if (isset($input['billing_interval']) && !isset($input['billing_cycle'])) {
            $input['billing_cycle'] = $input['billing_interval'];
        }

        $validator = Validator::make($input, [
            'name' => ['sometimes', 'required', 'string'],
            'price' => ['sometimes', 'required', 'integer', 'min:0'],
            'max_merchants' => ['sometimes', 'required', 'integer'],
            'max_api_calls_per_month' => ['sometimes', 'required', 'integer'],
            'max_transactions_per_month' => ['sometimes', 'required', 'integer'],
            'rate_limit_per_minute' => ['sometimes', 'required', 'integer'],
            'billing_cycle' => ['sometimes', 'in:monthly,quarterly,yearly'],
            'is_active' => ['sometimes', 'boolean'],
            'is_popular' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'features' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $oldValues = $plan->toArray();
        $plan->update($validator->validated());

        AuditLog::record(
            action: 'plan.updated',
            entity: 'SubscriptionPlan',
            entityId: (string) $plan->id,
            oldValues: $oldValues,
            newValues: $plan->toArray()
        );

        return ApiResponse::success($plan, 'Subscription plan updated');
    }
}
