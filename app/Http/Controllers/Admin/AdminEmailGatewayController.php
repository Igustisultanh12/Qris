<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Services\Mail\EmailGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminEmailGatewayController extends Controller
{
    public function __construct(
        protected EmailGatewayService $emailGateway
    ) {}

    public function index(): JsonResponse
    {
        $config = $this->emailGateway->getConfig();
        return ApiResponse::success($config, 'Email gateway configuration retrieved');
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mailer' => ['required', 'string', 'in:smtp,sendmail,log'],
            'host' => ['required_if:mailer,smtp', 'nullable', 'string'],
            'port' => ['required_if:mailer,smtp', 'nullable', 'integer'],
            'username' => ['nullable', 'string'],
            'password' => ['nullable', 'string'],
            'encryption' => ['nullable', 'string', 'in:tls,ssl,none'],
            'from_address' => ['required', 'email'],
            'from_name' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $this->emailGateway->updateConfig($validator->validated());

        AuditLog::record(
            action: 'email_gateway.updated',
            entity: 'Setting',
            newValues: [
                'mailer' => $request->input('mailer'),
                'host' => $request->input('host'),
                'port' => $request->input('port'),
                'from_address' => $request->input('from_address'),
            ]
        );

        return ApiResponse::success($this->emailGateway->getConfig(), 'Konfigurasi Email Gateway berhasil disimpan');
    }

    public function sendTest(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'recipient_email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $result = $this->emailGateway->sendTestEmail($request->input('recipient_email'));

        AuditLog::record(
            action: 'email_gateway.test_sent',
            entity: 'Setting',
            newValues: [
                'recipient' => $request->input('recipient_email'),
                'success' => $result['success'],
            ]
        );

        if (!$result['success']) {
            return ApiResponse::error($result['message'], $result, 500);
        }

        return ApiResponse::success($result, $result['message']);
    }
}
