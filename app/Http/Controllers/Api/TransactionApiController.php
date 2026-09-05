<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionApiController extends Controller
{
    /**
     * List transactions for the authenticated customer with filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $query = Transaction::where('customer_id', $customer->id)
            ->with(['merchant']);

        // Search by reference or transaction_number
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('transaction_number', 'like', "%{$search}%");
            });
        }

        // Filter by reference
        if ($reference = $request->input('reference')) {
            $query->where('reference', $reference);
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by merchant
        if ($merchantId = $request->input('merchant_id')) {
            $query->whereHas('merchant', function ($q) use ($merchantId) {
                $q->where('uuid', $merchantId)->orWhere('merchant_code', $merchantId);
            });
        }

        // Filter by amount range
        if ($minAmount = $request->input('min_amount')) {
            $query->where('amount', '>=', (int) $minAmount);
        }
        if ($maxAmount = $request->input('max_amount')) {
            $query->where('amount', '<=', (int) $maxAmount);
        }

        // Filter by date range
        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));
        $transactions = $query->latest()->paginate($perPage);

        return ApiResponse::success(
            TransactionResource::collection($transactions),
            'Transactions retrieved successfully',
            [
                'current_page' => $transactions->currentPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'last_page' => $transactions->lastPage(),
            ]
        );
    }

    /**
     * Get single transaction detail.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $transaction = Transaction::where('customer_id', $customer->id)
            ->where(function ($q) use ($id) {
                $q->where('transaction_number', $id)
                  ->orWhere('uuid', $id)
                  ->orWhere('reference', $id);
            })
            ->with(['merchant', 'feeDetail'])
            ->first();

        if (!$transaction) {
            return ApiResponse::error('Transaction not found', null, 404);
        }

        return ApiResponse::success(new TransactionResource($transaction));
    }
}
