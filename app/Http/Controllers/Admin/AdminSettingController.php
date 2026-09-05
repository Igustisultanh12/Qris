<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSettingController extends Controller
{
    public function index(): JsonResponse
    {
        $settings = Setting::all()->groupBy('group');
        return ApiResponse::success($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $payload = $request->input('settings', []);

        foreach ($payload as $key => $val) {
            Setting::set($key, $val);
        }

        AuditLog::record(
            action: 'settings.updated',
            entity: 'Setting',
            newValues: $payload
        );

        return ApiResponse::success(null, 'System settings updated successfully');
    }
}
