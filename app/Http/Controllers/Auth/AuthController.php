<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerProfile;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        protected TwoFactorService $twoFactorService
    ) {}

    /**
     * Customer registration.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        // 1. Create Customer entity
        $customer = Customer::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'business_name' => $validated['business_name'],
            'status' => 'active',
            'max_merchants' => 3,
        ]);

        CustomerProfile::create([
            'customer_id' => $customer->id,
            'notification_preferences' => ['email' => true, 'webhook' => true],
        ]);

        // 2. Create User account
        $user = User::create([
            'customer_id' => $customer->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => null, // OTP verification required
            'status' => 'pending',
        ]);

        $customerRole = Role::where('slug', 'customer')->first();
        if ($customerRole) {
            $user->roles()->attach($customerRole->id);
        }

        // Generate 6-digit OTP
        $otp = sprintf('%06d', random_int(100000, 999999));
        cache()->put("email_otp_{$user->email}", $otp, now()->addMinutes(15));

        AuditLog::record(
            action: 'user.registered',
            entity: 'User',
            entityId: (string) $user->id,
            newValues: ['email' => $user->email, 'customer_id' => $customer->id]
        );

        // Dispatch Welcome & OTP emails via Email Gateway
        try {
            app(\App\Services\Mail\EmailGatewayService::class)->sendWelcomeEmail($user, $customer);
            app(\App\Services\Mail\EmailGatewayService::class)->sendOtpEmail($user, $otp);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return ApiResponse::success([
            'email' => $user->email,
            'otp_required' => true,
        ], 'Pendaftaran berhasil. Silakan masukkan 6-digit kode OTP yang telah dikirim ke email Anda.', [], 201);
    }

    /**
     * Verify email OTP code.
     */
    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'min:6', 'max:6'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error('Akun pengguna tidak ditemukan.', null, 404);
        }

        $cachedOtp = cache()->get("email_otp_{$user->email}");

        // Accept cached OTP or master test OTP in local/debug environment
        $isValidOtp = ($cachedOtp && $cachedOtp === $request->otp)
            || (config('app.env') === 'local' && $request->otp === '123456');

        if (!$isValidOtp) {
            return ApiResponse::error('Kode OTP tidak valid atau telah kadaluarsa. Silakan minta kode baru.', null, 422);
        }

        // Mark verified & activate user
        $user->email_verified_at = now();
        $user->status = 'active';
        $user->save();

        cache()->forget("email_otp_{$user->email}");

        $customer = $user->customer;
        if ($customer && $customer->status === 'pending') {
            $customer->update(['status' => 'active']);
        }

        AuditLog::record(
            action: 'user.email_verified',
            entity: 'User',
            entityId: (string) $user->id,
            newValues: ['email' => $user->email, 'verified_at' => now()->toDateTimeString()]
        );

        // Send welcome email after verification
        try {
            if ($customer) {
                app(\App\Services\Mail\EmailGatewayService::class)->sendWelcomeEmail($user, $customer);
            }
        } catch (\Throwable $e) {}

        // Issue token
        $token = $user->createToken('auth-token')->plainTextToken;

        return ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles()->first()?->slug ?? 'customer',
                'customer' => $customer ? [
                    'id' => $customer->id,
                    'uuid' => $customer->uuid,
                    'business_name' => $customer->business_name,
                ] : null,
            ],
        ], 'Verifikasi email berhasil! Selamat datang di Qmis.');
    }

    /**
     * Resend email OTP code.
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return ApiResponse::error('Akun pengguna tidak ditemukan.', null, 404);
        }

        if ($user->email_verified_at !== null) {
            return ApiResponse::error('Akun email ini sudah terverifikasi. Silakan langsung login.', null, 400);
        }

        $otp = sprintf('%06d', random_int(100000, 999999));
        cache()->put("email_otp_{$user->email}", $otp, now()->addMinutes(15));

        try {
            app(\App\Services\Mail\EmailGatewayService::class)->sendOtpEmail($user, $otp);
        } catch (\Throwable $e) {}

        return ApiResponse::success([
            'email' => $user->email,
        ], 'Kode OTP baru telah berhasil dikirim ke email Anda.');
    }

    /**
     * Login.
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return ApiResponse::error('Invalid email or password', null, 401);
        }

        if ($user->email_verified_at === null) {
            $otp = sprintf('%06d', random_int(100000, 999999));
            cache()->put("email_otp_{$user->email}", $otp, now()->addMinutes(15));
            try {
                app(\App\Services\Mail\EmailGatewayService::class)->sendOtpEmail($user, $otp);
            } catch (\Throwable $e) {}

            return ApiResponse::success([
                'otp_required' => true,
                'email' => $user->email,
            ], 'Akun Anda belum diverifikasi. Kode OTP baru telah dikirimkan ke email Anda.');
        }

        if ($user->status !== 'active') {
            return ApiResponse::error('Your account is inactive or suspended', null, 403);
        }

        // Check if 2FA is required
        if ($user->hasTwoFactorEnabled()) {
            $tempToken = Str::random(40);
            cache()->put("2fa_temp_{$tempToken}", $user->id, 300); // 5 minutes

            return ApiResponse::success([
                'two_factor_required' => true,
                'temp_token' => $tempToken,
            ], '2FA verification code required');
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $role = $user->isSuperAdmin() ? 'admin' : 'customer';

        AuditLog::record(
            action: 'user.login',
            entity: 'User',
            entityId: (string) $user->id
        );

        return ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'is_admin' => $user->isSuperAdmin(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
                'customer' => $user->customer ? [
                    'id' => $user->customer->id,
                    'uuid' => $user->customer->uuid,
                    'business_name' => $user->customer->business_name,
                ] : null,
            ],
        ], 'Login successful');
    }

    /**
     * Verify 2FA TOTP code during login.
     */
    public function verifyTwoFactor(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'temp_token' => ['required', 'string'],
            'code' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Validation failed', $validator->errors(), 422);
        }

        $userId = cache()->get("2fa_temp_{$request->temp_token}");
        if (!$userId) {
            return ApiResponse::error('Two-factor session expired. Please login again.', null, 401);
        }

        $user = User::find($userId);
        if (!$user) {
            return ApiResponse::error('User not found', null, 404);
        }

        $code = trim($request->code);
        $verified = false;

        // Try standard TOTP code
        if (strlen($code) === 6 && ctype_digit($code)) {
            $verified = $this->twoFactorService->verify($user->two_factor_secret, $code);
        }

        // Try recovery code fallback
        if (!$verified && !empty($user->two_factor_recovery_codes)) {
            $verified = $this->twoFactorService->verifyAndConsumeRecoveryCode($user, $code);
        }

        if (!$verified) {
            return ApiResponse::error('Invalid authentication code or recovery code', null, 422);
        }

        cache()->forget("2fa_temp_{$request->temp_token}");

        $token = $user->createToken('auth-token')->plainTextToken;
        $role = $user->isSuperAdmin() ? 'admin' : 'customer';

        return ApiResponse::success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role,
                'is_admin' => $user->isSuperAdmin(),
                'customer' => $user->customer ? [
                    'id' => $user->customer->id,
                    'uuid' => $user->customer->uuid,
                    'business_name' => $user->customer->business_name,
                ] : null,
            ],
        ], '2FA verified successfully');
    }

    /**
     * Get authenticated user profile.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return ApiResponse::error('Unauthenticated', null, 401);
        }

        $role = $user->isSuperAdmin() ? 'admin' : 'customer';

        return ApiResponse::success([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $role,
            'is_admin' => $user->isSuperAdmin(),
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'customer' => $user->customer ? [
                'id' => $user->customer->id,
                'uuid' => $user->customer->uuid,
                'name' => $user->customer->name,
                'business_name' => $user->customer->business_name,
                'status' => $user->customer->status,
                'active_subscription' => $user->customer->activeSubscription ? [
                    'status' => $user->customer->activeSubscription->status,
                    'plan' => $user->customer->activeSubscription->plan?->name,
                    'ends_at' => $user->customer->activeSubscription->ends_at?->toDateString(),
                ] : null,
            ] : null,
        ]);
    }

    /**
     * Logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        return ApiResponse::success(null, 'Logged out successfully');
    }
}
