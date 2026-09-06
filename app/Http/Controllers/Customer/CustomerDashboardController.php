<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            return ApiResponse::success([
                'stats' => [
                    'total_merchants' => 0,
                    'max_merchants' => 0,
                    'total_transactions' => 0,
                    'generated_qr_count' => 0,
                    'paid_transactions_count' => 0,
                    'total_volume' => 0,
                    'api_calls_count' => 0,
                    'outstanding_invoices_count' => 0,
                    'outstanding_invoices_amount' => 0,
                ],
                'subscription' => null,
                'recent_transactions' => [],
                'chart' => [],
            ]);
        }

        // Metrics
        $totalMerchants = $customer->merchants()->count();
        $totalTransactions = $customer->transactions()->count();
        $totalGenerated = $customer->transactions()->where('status', 'generated')->count();
        $totalPaid = $customer->transactions()->where('status', 'paid')->count();
        $totalVolume = (int) $customer->transactions()->sum('amount');
        $apiCalls = $customer->apiKeys()->withCount('usageLogs')->get()->sum('usage_logs_count');

        // Subscription & Invoices
        $subscription = $customer->activeSubscription;
        $outstandingInvoices = $customer->invoices()->whereIn('status', ['pending', 'overdue'])->count();
        $outstandingAmount = (int) $customer->invoices()->whereIn('status', ['pending', 'overdue'])->sum('total');

        // Recent transactions
        $recentTransactions = $customer->transactions()
            ->with('merchant')
            ->latest()
            ->take(8)
            ->get();

        // 14-day chart
        $dailyChart = $customer->transactions()
            ->where('created_at', '>=', now()->subDays(14)->startOfDay())
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return ApiResponse::success([
            'stats' => [
                'total_merchants' => $totalMerchants,
                'max_merchants' => $customer->max_merchants,
                'total_transactions' => $totalTransactions,
                'generated_qr_count' => $totalGenerated,
                'paid_transactions_count' => $totalPaid,
                'total_volume' => $totalVolume,
                'api_calls_count' => $apiCalls,
                'outstanding_invoices_count' => $outstandingInvoices,
                'outstanding_invoices_amount' => $outstandingAmount,
            ],
            'subscription' => $subscription ? [
                'status' => $subscription->status,
                'plan_name' => $subscription->plan?->name,
                'price' => $subscription->price,
                'ends_at' => $subscription->ends_at?->toDateString(),
                'is_grace_period' => $subscription->isInGracePeriod(),
            ] : null,
            'recent_transactions' => $recentTransactions,
            'chart' => $dailyChart,
        ]);
    }
}
