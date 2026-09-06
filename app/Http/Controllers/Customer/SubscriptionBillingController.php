<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Services\Billing\BillingService;
use App\Services\Billing\PaymentGatewayManager;
use App\Services\Qris\QrisConverter;
use App\Services\Qris\QrisGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SubscriptionBillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService,
        protected PaymentGatewayManager $gatewayManager,
        protected QrisConverter $qrisConverter
    ) {}

    public function plans(): JsonResponse
    {
        $plans = SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();

        return ApiResponse::success($plans);
    }

    public function current(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::success(['subscription' => null]);
        }

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
        if (!$customer) {
            return ApiResponse::success([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'total' => 0,
            ]);
        }

        $invoices = $customer->invoices()
            ->with(['items', 'payments'])
            ->latest()
            ->paginate(15);

        return ApiResponse::success($invoices);
    }

    public function createInvoice(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::error('Hanya akun pelanggan yang dapat membuat faktur langganan.', null, 403);
        }

        $validator = Validator::make($request->all(), [
            'plan_id' => ['nullable', 'integer', 'exists:subscription_plans,id'],
            'plan_slug' => ['nullable', 'string', 'exists:subscription_plans,slug'],
            'coupon_code' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $plan = null;
        if ($request->filled('plan_id')) {
            $plan = SubscriptionPlan::find($request->plan_id);
        } elseif ($request->filled('plan_slug')) {
            $plan = SubscriptionPlan::where('slug', $request->plan_slug)->first();
        }

        if (!$plan) {
            return ApiResponse::error('Paket langganan harus dipilih.', null, 422);
        }

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
        if (!$customer) {
            return ApiResponse::error('Hanya akun pelanggan yang dapat melakukan pembayaran.', null, 403);
        }

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

    /**
     * Generate dynamic QRIS for invoice payment using platform static QRIS.
     */
    public function getInvoiceQris(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::error('Hanya akun pelanggan yang dapat mengakses QRIS invoice.', null, 403);
        }

        $invoice = $customer->invoices()
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->with(['subscription.plan'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return ApiResponse::success([
                'invoice' => $invoice,
                'is_paid' => true,
            ], 'Invoice telah berstatus lunas.');
        }

        // Retrieve platform static QRIS configured by Super Admin in Settings
        $staticQris = Setting::get('platform_qris_static');
        if (empty($staticQris)) {
            $staticQris = '00020101021126620014ID.LINKAJA.WWW01189360091100220945610211000000000010303UMI51440014ID.CO.QRIS.WWW0215ID10200210000010303UMI5204581253033605802ID5920PT KREATIF SKY ABADI6007JAKARTA61051011062070703A016304B835';
        }


        $amount = (int) $invoice->total;
        $conversion = $this->qrisConverter->convert($staticQris, $amount);

        if (!$conversion->success) {
            return ApiResponse::error('Gagal membuat QRIS dinamis: ' . implode(', ', $conversion->errors), null, 500);
        }

        $dynamicPayload = $conversion->dynamicPayload;
        $svg = QrisGenerator::generateSvg($dynamicPayload);
        $svgDataUri = QrisGenerator::generateSvgDataUri($dynamicPayload);
        $parsed = $this->qrisConverter->parse($dynamicPayload);

        return ApiResponse::success([
            'invoice' => [
                'id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'status' => $invoice->status,
                'due_date' => $invoice->due_date?->toIso8601String(),
                'plan_name' => $invoice->subscription?->plan?->name,
                'billing_period' => $invoice->subscription?->plan?->billing_cycle,
            ],
            'qris' => [
                'payload' => $dynamicPayload,
                'qr_svg' => $svg,
                'qr_svg_data_uri' => $svgDataUri,
                'merchant_name' => $parsed->merchantName ?: Setting::get('platform_qris_merchant_name', 'PT KREATIF SKY ABADI'),
                'merchant_city' => $parsed->merchantCity ?: Setting::get('platform_qris_merchant_city', 'JAKARTA'),
                'postal_code' => $parsed->postalCode ?: '10110',
                'amount' => $amount,
                'method' => 'dynamic',
                'point_of_initiation' => '12',
                'fee' => 0,
            ],
        ], 'QRIS Dinamis untuk pembayaran tagihan berhasil dibuat');
    }

    /**
     * Simulate instant QRIS payment for subscription invoice.
     */
    public function simulatePaid(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::error('Hanya akun pelanggan yang dapat memproses pembayaran.', null, 403);
        }

        $invoice = $customer->invoices()
            ->where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->with(['subscription.plan'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return ApiResponse::success([
                'invoice' => $invoice,
                'already_paid' => true,
            ], 'Tagihan ini telah dibayar lunas.');
        }

        // Record successful payment transaction
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-QRIS-' . strtoupper(Str::random(8)),
            'amount' => $invoice->total,
            'payment_method' => 'qris_dynamic',
            'gateway' => 'qris_platform',
            'status' => 'success',
            'paid_at' => now(),
            'gateway_payload' => [
                'type' => 'simulated_qris_payment',
                'channel' => 'QRIS Dinamis PT Kreatif Sky Abadi',
                'reference_id' => 'REF-' . strtoupper(Str::random(12)),
                'amount' => $invoice->total,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);

        // Mark invoice paid and automatically activate/extend customer subscription
        $this->billingService->markInvoicePaid($invoice, $payment);

        $freshCustomer = $customer->fresh();
        $subscription = $freshCustomer->activeSubscription;

        return ApiResponse::success([
            'invoice' => $invoice->fresh()->load('subscription.plan'),
            'payment' => $payment,
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'uuid' => $subscription->uuid,
                'status' => $subscription->status,
                'price' => $subscription->price,
                'starts_at' => $subscription->starts_at?->toDateString(),
                'ends_at' => $subscription->ends_at?->toDateString(),
                'plan' => $subscription->plan,
            ] : null,
        ], 'Pembayaran QRIS berhasil dikonfirmasi lunas! Paket langganan Anda telah aktif.');
    }
}

