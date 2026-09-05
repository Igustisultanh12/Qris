<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::with(['user', 'customer']);

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        if ($entity = $request->input('entity')) {
            $query->where('entity', $entity);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        $logs = $query->latest('created_at')->paginate(20);
        return ApiResponse::success($logs);
    }
}
