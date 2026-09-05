<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Services\Billing\BillingService;
use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected PaymentGatewayManager $gatewayManager
    ) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        return ApiResponse::success($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $subscription = $customer->activeSubscription;

        return ApiResponse::success([
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'uuid' => $subscription->uuid,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'starts_at' => $subscription->starts_at?->toDateString(),
                'ends_at' => $subscription->ends_at?->toDateString(),
                'is_in_grace_period' => $subscription->isInGracePeriod(),
                'plan' => $subscription->plan,
            ] : null,
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        $invoices = $customer->invoices()
            ->with(['items', 'payments'])
            ->latest()
            ->paginate(15);

        return ApiResponse::success($invoices);
    }

    public function createInvoice(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;

        $validator = Validator::make($request->all(), [
            'plan_slug' => ['required', 'exists:subscription_plans,slug'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $plan = SubscriptionPlan::where('slug', $request->plan_slug)->firstOrFail();

        $coupon = null;
        if ($code = $request->coupon_code) {
            $coupon = Coupon::where('code', strtoupper($code))->first();
        }

        $invoice = $this->billingService->createSubscriptionInvoice(
            customer: $customer,
            plan: $plan,
            coupon: $coupon
        );

        $invoice->load(['items', 'subscription.plan']);

        return ApiResponse::success($invoice, 'Invoice generated successfully', [], 201);
    }

    public function pay(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $invoice = $customer->invoices()->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();

        if ($invoice->status === 'paid') {
            return ApiResponse::error('This invoice is already paid', null, 422);
        }

        $gatewayName = $request->input('gateway');
        $driver = $this->gatewayManager->driver($gatewayName);

        $payment = $driver->createPayment($invoice, [
            'method' => $request->input('method', 'bank_transfer'),
        ]);

        return ApiResponse::success([
            'payment' => $payment,
            'invoice' => $invoice,
        ], 'Payment checkout initialized');
    }
}
