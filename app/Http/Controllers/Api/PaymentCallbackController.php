<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Services\Billing\BillingService;
use App\Services\Billing\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentCallbackController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $gatewayManager,
        protected BillingService $billingService
    ) {}

    /**
     * Handle incoming payment gateway callback/webhook.
     */
    public function handle(Request $request, string $gateway): JsonResponse
    {
        Log::info("Payment webhook callback received for gateway [{$gateway}]", [
            'gateway' => $gateway,
            'ip' => $request->ip(),
            'payload' => $request->all(),
        ]);

        try {
            $driver = $this->gatewayManager->driver($gateway);
            $payment = $driver->handleCallback($request);

            if ($payment->status === 'success') {
                $this->billingService->markInvoicePaid($payment->invoice, $payment);
            }

            return ApiResponse::success([
                'payment_number' => $payment->payment_number,
                'status' => $payment->status,
            ], 'Payment callback processed successfully');
        } catch (\InvalidArgumentException $e) {
            Log::warning("Payment callback failed signature or validation: {$e->getMessage()}", [
                'gateway' => $gateway,
            ]);
            return ApiResponse::error($e->getMessage(), null, 400);
        } catch (\Throwable $e) {
            Log::error("Error processing payment callback: {$e->getMessage()}", [
                'gateway' => $gateway,
                'trace' => $e->getTraceAsString(),
            ]);
            return ApiResponse::error('Internal error processing callback', null, 500);
        }
    }
}
