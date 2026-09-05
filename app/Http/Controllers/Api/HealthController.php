<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        $status = 'healthy';
        $services = [];

        // Database check
        try {
            DB::connection()->getPdo();
            $services['database'] = ['status' => 'healthy', 'message' => 'Connected successfully'];
        } catch (\Throwable $e) {
            $status = 'critical';
            $services['database'] = ['status' => 'critical', 'message' => $e->getMessage()];
        }

        // Cache check
        try {
            Cache::put('health_test', 1, 10);
            $cacheCheck = Cache::get('health_test') === 1;
            Cache::forget('health_test');
            $services['cache'] = ['status' => $cacheCheck ? 'healthy' : 'warning', 'message' => $cacheCheck ? 'Operational' : 'Cache read failed'];
        } catch (\Throwable $e) {
            $status = 'warning';
            $services['cache'] = ['status' => 'warning', 'message' => $e->getMessage()];
        }

        // Storage check
        try {
            $disk = Storage::disk('local');
            $disk->put('health.txt', 'ok');
            $disk->delete('health.txt');
            $services['storage'] = ['status' => 'healthy', 'message' => 'Disk writable'];
        } catch (\Throwable $e) {
            $status = 'warning';
            $services['storage'] = ['status' => 'warning', 'message' => $e->getMessage()];
        }

        return ApiResponse::success([
            'status' => $status,
            'version' => '1.0.0',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
        ], 'System health check completed');
    }
}
