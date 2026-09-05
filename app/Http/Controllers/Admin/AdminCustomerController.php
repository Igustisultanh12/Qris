<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['activeSubscription.plan', 'profile'])
            ->withCount(['merchants', 'transactions']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('business_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $customers = $query->latest()->paginate(15);
        return ApiResponse::success($customers);
    }

    public function show(string $id): JsonResponse
    {
        $customer = Customer::where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))
            ->with(['activeSubscription.plan', 'profile', 'merchants', 'invoices' => fn ($q) => $q->latest()->take(5)])
            ->withCount(['merchants', 'transactions'])
            ->firstOrFail();

        return ApiResponse::success($customer);
    }

    public function updateStatus(Request $request, string $id): JsonResponse
    {
        $customer = Customer::where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();
        $newStatus = $request->input('status');

        if (!in_array($newStatus, ['active', 'suspended', 'pending'], true)) {
            return ApiResponse::error('Invalid customer status', null, 422);
        }

        $oldStatus = $customer->status;
        $customer->update(['status' => $newStatus]);

        AuditLog::record(
            action: 'customer.status_changed',
            entity: 'Customer',
            entityId: (string) $customer->id,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus]
        );

        return ApiResponse::success($customer, "Customer status updated to {$newStatus}");
    }

    public function updateSubscription(Request $request, string $id): JsonResponse
    {
        $customer = Customer::where(fn ($q) => $q->where('uuid', $id)->orWhere('id', $id))->firstOrFail();
        $planSlug = $request->input('plan_slug');
        $plan = SubscriptionPlan::where('slug', $planSlug)->firstOrFail();

        $sub = $customer->activeSubscription;
        if ($sub) {
            $sub->update([
                'plan_id' => $plan->id,
                'price' => $plan->price,
                'status' => 'active',
                'ends_at' => now()->addMonths(1),
            ]);
        }

        $customer->update(['max_merchants' => $plan->max_merchants]);

        AuditLog::record(
            action: 'customer.subscription_changed',
            entity: 'Customer',
            entityId: (string) $customer->id,
            newValues: ['plan' => $plan->name]
        );

        return ApiResponse::success($customer->load('activeSubscription.plan'), 'Customer subscription updated');
    }
}
