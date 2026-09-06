<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailGatewayController;
use App\Http\Controllers\Admin\AdminFinancialController;
use App\Http\Controllers\Admin\AdminPlanController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\MerchantApiController;
use App\Http\Controllers\Api\PaymentCallbackController;
use App\Http\Controllers\Api\QrisApiController;
use App\Http\Controllers\Api\TransactionApiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Customer\ApiKeyController;
use App\Http\Controllers\Customer\CustomerDashboardController;
use App\Http\Controllers\Customer\MerchantController;
use App\Http\Controllers\Customer\QrisGeneratorController;
use App\Http\Controllers\Customer\SubscriptionBillingController;
use App\Http\Controllers\Customer\CustomerTransactionController;
use App\Http\Controllers\Customer\WebhookController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Qmis (PT Kreatif Sky Abadi)
|--------------------------------------------------------------------------
*/

// ==========================================
// 1. PUBLIC MARKETING & PLATFORM AUTH
// ==========================================
Route::prefix('auth')->middleware(['assign.request.id'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-2fa', [AuthController::class, 'verifyTwoFactor']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::get('/plans', [SubscriptionBillingController::class, 'plans']);

// ==========================================
// 2. VERSIONED REST API PLATFORM (/api/v1)
// ==========================================
Route::prefix('v1')->middleware(['assign.request.id'])->group(function () {
    // Health Check
    Route::get('/health', [HealthController::class, 'index']);

    // Payment Gateway Callbacks (Signed)
    Route::post('/billing/callbacks/{gateway}', [PaymentCallbackController::class, 'handle']);

    // Authenticated Partner Endpoints (API Key)
    Route::middleware(['api.key', 'api.rate', 'api.logger'])->group(function () {
        Route::prefix('qris')->group(function () {
            Route::post('/parse', [QrisApiController::class, 'parse']);
            Route::post('/validate', [QrisApiController::class, 'validateQris']);
            Route::post('/dynamic', [QrisApiController::class, 'createDynamic']);
            Route::get('/{id}', [QrisApiController::class, 'show']);
            Route::post('/{id}/cancel', [QrisApiController::class, 'cancel']);
            Route::post('/{id}/simulate-paid', [QrisApiController::class, 'simulatePaid']);
        });

        Route::prefix('transactions')->group(function () {
            Route::get('/', [TransactionApiController::class, 'index']);
            Route::get('/{id}', [TransactionApiController::class, 'show']);
            Route::post('/{id}/simulate-paid', [QrisApiController::class, 'simulatePaid']);
        });

        Route::prefix('merchants')->group(function () {
            Route::get('/', [MerchantApiController::class, 'index']);
            Route::get('/{id}', [MerchantApiController::class, 'show']);
        });
    });
});

// ==========================================
// 3. AUTHENTICATED WEB PORTAL (Sanctum)
// ==========================================
Route::middleware(['auth:sanctum', 'assign.request.id'])->group(function () {

    // User Profile & 2FA
    Route::prefix('user')->group(function () {
        Route::put('/profile', [UserProfileController::class, 'updateProfile']);
        Route::put('/password', [UserProfileController::class, 'updatePassword']);
        Route::post('/2fa/setup', [UserProfileController::class, 'setupTwoFactor']);
        Route::post('/2fa/confirm', [UserProfileController::class, 'confirmTwoFactor']);
        Route::post('/2fa/disable', [UserProfileController::class, 'disableTwoFactor']);
    });

    // Customer Portal
    Route::prefix('customer')->group(function () {
        Route::get('/dashboard', [CustomerDashboardController::class, 'index']);

        // Merchants
        Route::apiResource('merchants', MerchantController::class);

        // QRIS Interactive Generator
        Route::post('/qris/validate-static', [QrisGeneratorController::class, 'validateStatic']);
        Route::post('/qris/generate', [QrisGeneratorController::class, 'generate']);

        // Transactions
        Route::get('/transactions', [CustomerTransactionController::class, 'index']);
        Route::get('/transactions/{id}', [CustomerTransactionController::class, 'show']);
        Route::post('/transactions/{id}/cancel', [CustomerTransactionController::class, 'cancel']);
        Route::post('/transactions/{id}/simulate-paid', [CustomerTransactionController::class, 'simulatePaid']);

        // API Keys & Webhooks
        Route::apiResource('api-keys', ApiKeyController::class);
        Route::apiResource('webhooks', WebhookController::class);
        Route::post('webhooks/{id}/test', [WebhookController::class, 'test']);

        // Billing & Invoices
        Route::get('/billing/current', [SubscriptionBillingController::class, 'current']);
        Route::get('/billing/invoices', [SubscriptionBillingController::class, 'invoices']);
        Route::post('/billing/invoices/create', [SubscriptionBillingController::class, 'createInvoice']);
        Route::get('/billing/invoices/{id}/qris', [SubscriptionBillingController::class, 'getInvoiceQris']);
        Route::post('/billing/invoices/{id}/simulate-paid', [SubscriptionBillingController::class, 'simulatePaid']);
        Route::post('/billing/invoices/{id}/pay', [SubscriptionBillingController::class, 'pay']);
    });

    // Common Helpdesk / Tickets
    Route::apiResource('tickets', SupportTicketController::class);
    Route::post('tickets/{id}/reply', [SupportTicketController::class, 'reply']);

    // Super Admin Portal
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Customers Management
        Route::get('/customers', [AdminCustomerController::class, 'index']);
        Route::get('/customers/{id}', [AdminCustomerController::class, 'show']);
        Route::put('/customers/{id}/status', [AdminCustomerController::class, 'updateStatus']);
        Route::put('/customers/{id}/subscription', [AdminCustomerController::class, 'updateSubscription']);

        // Plans & Tariffs
        Route::apiResource('plans', AdminPlanController::class);

        // Financial Reports & CSV
        Route::get('/financial/overview', [AdminFinancialController::class, 'overview']);
        Route::get('/financial/rankings', [AdminFinancialController::class, 'rankings']);
        Route::get('/financial/export-csv', [AdminFinancialController::class, 'exportCsv']);

        // System Settings & Audit Logs
        Route::get('/settings', [AdminSettingController::class, 'index']);
        Route::post('/settings/update', [AdminSettingController::class, 'update']);
        Route::post('/settings/qris-preview', [AdminSettingController::class, 'previewQris']);
        Route::get('/audit-logs', [AdminAuditController::class, 'index']);


        // Email Gateway System
        Route::get('/email-gateway', [AdminEmailGatewayController::class, 'index']);
        Route::post('/email-gateway', [AdminEmailGatewayController::class, 'update']);
        Route::post('/email-gateway/test', [AdminEmailGatewayController::class, 'sendTest']);
    });
});
