<?php

namespace App\Services\Report;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Merchant;
use App\Models\Transaction;
use App\Models\TransactionFee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Get consolidated financial overview.
     */
    public function getOverview(?string $startDate = null, ?string $endDate = null, ?int $customerId = null): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        // Transactions Query
        $txQuery = Transaction::whereBetween('created_at', [$start, $end]);
        if ($customerId) {
            $txQuery->where('customer_id', $customerId);
        }

        $totalTransactions = (clone $txQuery)->count();
        $paidTransactions = (clone $txQuery)->where('status', 'paid')->count();
        $grossVolume = (int) (clone $txQuery)->sum('amount');
        $totalFees = (int) (clone $txQuery)->sum('fee');
        $netVolume = $grossVolume - $totalFees;

        // Invoices Query (Subscription revenue)
        $invQuery = Invoice::whereBetween('created_at', [$start, $end]);
        if ($customerId) {
            $invQuery->where('customer_id', $customerId);
        }

        $subscriptionRevenue = (int) (clone $invQuery)->where('status', 'paid')->sum('total');
        $pendingInvoiceAmount = (int) (clone $invQuery)->where('status', 'pending')->sum('total');
        $overdueInvoiceAmount = (int) (clone $invQuery)->where('status', 'overdue')->sum('total');

        // Total Platform Revenue = Service fees + Subscription revenue
        $totalRevenue = $totalFees + $subscriptionRevenue;

        // Daily volume series for chart
        $dailyVolume = (clone $txQuery)
            ->selectRaw("DATE(created_at) as date, COUNT(*) as count, SUM(amount) as volume, SUM(fee) as fee_sum")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return [
            'period' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'summary' => [
                'total_transactions' => $totalTransactions,
                'paid_transactions' => $paidTransactions,
                'gross_volume' => $grossVolume,
                'platform_fees' => $totalFees,
                'net_merchant_volume' => $netVolume,
                'subscription_revenue' => $subscriptionRevenue,
                'total_platform_revenue' => $totalRevenue,
                'pending_invoices_amount' => $pendingInvoiceAmount,
                'overdue_invoices_amount' => $overdueInvoiceAmount,
            ],
            'daily_chart' => $dailyVolume,
        ];
    }

    /**
     * Customer revenue rankings.
     */
    public function getCustomerRankings(int $limit = 10): array
    {
        return Customer::withCount('transactions')
            ->withSum('transactions as total_volume', 'amount')
            ->withSum('invoices as subscription_paid', 'total')
            ->orderByDesc('total_volume')
            ->take($limit)
            ->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id,
                    'uuid' => $c->uuid,
                    'name' => $c->name,
                    'business_name' => $c->business_name,
                    'total_transactions' => $c->transactions_count,
                    'total_volume' => (int) $c->total_volume,
                    'subscription_paid' => (int) $c->subscription_paid,
                ];
            })
            ->toArray();
    }

    /**
     * Merchant revenue rankings.
     */
    public function getMerchantRankings(?int $customerId = null, int $limit = 10): array
    {
        $query = Merchant::withCount('transactions')
            ->withSum('transactions as total_volume', 'amount')
            ->orderByDesc('total_volume');

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return $query->take($limit)
            ->get()
            ->map(function ($m) {
                return [
                    'id' => $m->id,
                    'uuid' => $m->uuid,
                    'merchant_code' => $m->merchant_code,
                    'name' => $m->name,
                    'store_name' => $m->store_name,
                    'city' => $m->city,
                    'total_transactions' => $m->transactions_count,
                    'total_volume' => (int) $m->total_volume,
                ];
            })
            ->toArray();
    }

    /**
     * Generate CSV content for transactions report.
     */
    public function exportTransactionsCsv(?string $startDate = null, ?string $endDate = null, ?int $customerId = null): string
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();

        $query = Transaction::with(['customer', 'merchant'])
            ->whereBetween('created_at', [$start, $end]);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $transactions = $query->latest()->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Transaction Number', 'Customer', 'Merchant', 'Reference', 'Amount', 'Fee', 'Total', 'Fee Mode', 'Status', 'Date']);

        foreach ($transactions as $tx) {
            fputcsv($output, [
                $tx->transaction_number,
                $tx->customer?->name,
                $tx->merchant?->name,
                $tx->reference,
                $tx->amount,
                $tx->fee,
                $tx->total,
                $tx->fee_mode,
                $tx->status,
                $tx->created_at->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
