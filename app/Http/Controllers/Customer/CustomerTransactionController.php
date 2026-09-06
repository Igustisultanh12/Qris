<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\Transaction;
use App\Services\Qris\QrisGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerTransactionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user()->customer;
        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }

        $query = $customer->transactions()->with(['merchant:id,name,store_name,merchant_code']);

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('merchant_id')) {
            $query->where('merchant_id', $request->query('merchant_id'));
        }

        if ($request->filled('search')) {
            $search = '%' . trim($request->query('search')) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', $search)
                  ->orWhere('id', 'like', $search)
                  ->orWhere('uuid', 'like', $search);
            });
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->query('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->query('to_date'));
        }

        $perPage = min((int) $request->query('per_page', 15), 100);
        $transactions = $query->latest()->paginate($perPage);

        return ApiResponse::paginated($transactions, 'Transactions retrieved');
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $transaction = $customer->transactions()
            ->with(['merchant', 'fees', 'customer'])
            ->where(fn ($q) => $q->where('id', $id)->orWhere('uuid', $id)->orWhere('reference', $id))
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        $data = $transaction->toArray();
        if ($transaction->qris_dynamic) {
            $data['qr_svg'] = QrisGenerator::generateSvg($transaction->qris_dynamic);
            $data['qr_png'] = QrisGenerator::generatePngDataUri($transaction->qris_dynamic);
        }

        return ApiResponse::success($data);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $transaction = $customer->transactions()
            ->where(fn ($q) => $q->where('id', $id)->orWhere('uuid', $id))
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        if ($transaction->status !== 'generated') {
            return ApiResponse::error("Cannot cancel transaction with status '{$transaction->status}'", null, 400);
        }

        $transaction->update([
            'status' => 'cancelled',
            'metadata' => array_merge($transaction->metadata ?? [], ['cancelled_by' => 'customer', 'cancelled_at' => now()->toIso8601String()]),
        ]);

        return ApiResponse::success($transaction, 'Transaction cancelled successfully');
    }

    public function simulatePaid(Request $request, string $id): JsonResponse
    {
        $customer = $request->user()->customer;
        $transaction = $customer->transactions()
            ->where(fn ($q) => $q->where('id', $id)->orWhere('uuid', $id)->orWhere('reference', $id))
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        if ($transaction->status === 'paid') {
            return ApiResponse::success($transaction, 'Transaksi sudah berstatus lunas');
        }

        if (in_array($transaction->status, ['cancelled', 'expired'], true)) {
            return ApiResponse::error("Tidak dapat membayar transaksi berstatus '{$transaction->status}'", null, 422);
        }

        $paymentRef = 'SIM-WEB-' . strtoupper(\Illuminate\Support\Str::random(10));
        $transactionService = app(\App\Services\Transaction\TransactionService::class);
        $transaction = $transactionService->markAsPaid(
            $transaction,
            $paymentRef,
            ['simulated' => true, 'source' => 'web_portal', 'paid_at' => now()->toIso8601String()]
        );

        return ApiResponse::success($transaction, 'Simulasi pembayaran sukses! Status transaksi kini LUNAS (PAID) dan webhook notifikasi telah dikirim.');
    }
}
