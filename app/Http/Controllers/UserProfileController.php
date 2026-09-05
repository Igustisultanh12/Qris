<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'business_name' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $user->update([
            'name' => $request->name,
            'phone' => $request->phone,
        ]);

        if ($user->customer && $request->filled('business_name')) {
            $user->customer->update(['business_name' => $request->business_name]);
        }

        return ApiResponse::success($user, 'Profile updated successfully');
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return ApiResponse::error('Current password does not match', null, 422);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::record(action: 'user.password_changed', entity: 'User', entityId: (string) $user->id);

        return ApiResponse::success(null, 'Password updated successfully');
    }

    public function setupTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();
        $secret = $this->twoFactorService->generateSecretKey();
        $qrUrl = $this->twoFactorService->getQrCodeUrl($user, $secret);

        // Store unconfirmed secret in user model
        $user->update(['two_factor_secret' => $secret]);

        return ApiResponse::success([
            'secret' => $secret,
            'qr_url' => $qrUrl,
        ], '2FA setup initialized');
    }

    public function confirmTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();
        $code = trim((string) $request->input('code'));

        if (empty($user->two_factor_secret)) {
            return ApiResponse::error('Please initialize 2FA setup first', null, 400);
        }

        if (!$this->twoFactorService->verify($user->two_factor_secret, $code)) {
            return ApiResponse::error('Invalid verification code', null, 422);
        }

        $recoveryCodes = $this->twoFactorService->generateRecoveryCodes(8);

        $user->update([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ]);

        AuditLog::record(action: 'user.2fa_enabled', entity: 'User', entityId: (string) $user->id);

        return ApiResponse::success([
            'recovery_codes' => $recoveryCodes,
        ], 'Two-Factor Authentication enabled successfully! Please save your recovery codes safely.');
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!Hash::check($request->input('password', ''), $user->password)) {
            return ApiResponse::error('Password verification required to disable 2FA', null, 422);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);

        AuditLog::record(action: 'user.2fa_disabled', entity: 'User', entityId: (string) $user->id);

        return ApiResponse::success(null, 'Two-Factor Authentication disabled');
    }
}
