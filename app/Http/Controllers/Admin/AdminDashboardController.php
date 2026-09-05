<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\ApiUsageLog;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\WebhookDelivery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // 1. Statistics
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $totalMerchants = Merchant::count();
        $activeMerchants = Merchant::where('status', 'active')->count();

        $totalTransactions = Transaction::count();
        $paidTransactions = Transaction::where('status', 'paid')->count();
        $failedTransactions = Transaction::whereIn('status', ['failed', 'expired'])->count();

        $transactionVolume = (int) Transaction::sum('amount');
        $platformFeeRevenue = (int) Transaction::sum('fee');
        $subscriptionRevenue = (int) Invoice::where('status', 'paid')->sum('total');
        $monthlyRevenue = $platformFeeRevenue + $subscriptionRevenue;

        $outstandingInvoices = Invoice::whereIn('status', ['pending', 'overdue'])->count();
        $outstandingAmount = (int) Invoice::whereIn('status', ['pending', 'overdue'])->sum('total');

        $totalApiCalls = ApiUsageLog::count();
        $apiErrors = ApiUsageLog::where('response_status', '>=', 400)->count();
        $webhookErrors = WebhookDelivery::where('is_success', false)->count();

        // 2. Daily Chart (Last 14 days)
        $chartData = Transaction::where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume, SUM(fee) as fees")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3. Recent Audit Logs
        $recentAudit = AuditLog::with('user')
            ->latest('created_at')
            ->take(8)
            ->get();

        return ApiResponse::success([
            'stats' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'total_merchants' => $totalMerchants,
                'active_merchants' => $activeMerchants,
                'total_transactions' => $totalTransactions,
                'paid_transactions' => $paidTransactions,
                'failed_transactions' => $failedTransactions,
                'transaction_volume' => $transactionVolume,
                'monthly_revenue' => $monthlyRevenue,
                'platform_fee_revenue' => $platformFeeRevenue,
                'subscription_revenue' => $subscriptionRevenue,
                'outstanding_invoices_count' => $outstandingInvoices,
                'outstanding_invoices_amount' => $outstandingAmount,
                'total_api_calls' => $totalApiCalls,
                'api_errors_count' => $apiErrors,
                'webhook_errors_count' => $webhookErrors,
            ],
            'chart' => $chartData,
            'recent_audits' => $recentAudit,
        ]);
    }
}
