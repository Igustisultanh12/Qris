<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Services\Qris\QrisParser;
use App\Services\Qris\QrisValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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

    /**
     * Parse and preview QRIS payload for admin validation.
     */
    public function previewQris(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payload' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Payload QRIS wajib diisi', $validator->errors(), 422);
        }

        $payload = trim($request->input('payload'));
        $validation = QrisValidator::validate($payload);

        if (!$validation->valid) {
            return ApiResponse::error('Payload QRIS tidak memenuhi standar EMVCo / ASPI', [
                'is_valid' => false,
                'errors' => $validation->errors,
            ], 422);
        }

        $parsed = QrisParser::parse($payload);

        return ApiResponse::success([
            'is_valid' => true,
            'merchant_name' => $parsed->merchantName,
            'merchant_city' => $parsed->merchantCity,
            'postal_code' => $parsed->postalCode,
            'method' => $parsed->method,
            'point_of_initiation' => $parsed->method === 'dynamic' ? '12' : '11',
            'crc' => $parsed->crc,
            'crc_valid' => true,
            'acquirers' => array_map(fn ($acq) => [
                'tag' => $acq->tag,
                'acquirer_name' => $acq->globallyUniqueId,
                'national_number' => $acq->globallyUniqueId,
                'merchant_id' => $acq->merchantId,
                'merchant_criteria' => $acq->merchantCriteria,
            ], $parsed->merchantAccountInfo),
        ], 'Payload QRIS valid');
    }
}


