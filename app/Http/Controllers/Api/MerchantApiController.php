<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MerchantResource;
use App\Http\Responses\ApiResponse;
use App\Models\Customer;
use App\Models\Merchant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MerchantApiController extends Controller
{
    /**
     * List all merchants owned by the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $query = Merchant::where('customer_id', $customer->id)
            ->with(['primaryQris']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('merchant_code', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = min(100, max(1, (int) $request->input('per_page', 15)));
        $merchants = $query->latest()->paginate($perPage);

        return ApiResponse::success(
            MerchantResource::collection($merchants),
            'Merchants retrieved successfully',
            [
                'current_page' => $merchants->currentPage(),
                'per_page' => $merchants->perPage(),
                'total' => $merchants->total(),
                'last_page' => $merchants->lastPage(),
            ]
        );
    }

    /**
     * Get details of a single merchant.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        /** @var Customer $customer */
        $customer = $request->attributes->get('customer');

        $merchant = Merchant::where('customer_id', $customer->id)
            ->where(function ($q) use ($id) {
                $q->where('uuid', $id)->orWhere('merchant_code', $id);
            })
            ->with(['primaryQris'])
            ->first();

        if (!$merchant) {
            return ApiResponse::error('Merchant not found', null, 404);
        }

        return ApiResponse::success(new MerchantResource($merchant));
    }
}
