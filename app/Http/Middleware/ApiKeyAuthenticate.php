<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken() ?: $request->header('X-API-Key');

        if (empty($token)) {
            return ApiResponse::error('API Key is required in Authorization header or X-API-Key', null, 401);
        }

        $hash = hash('sha256', trim($token));
        $apiKey = ApiKey::where('key_hash', $hash)->first();

        if (!$apiKey || !$apiKey->isValid()) {
            return ApiResponse::error('Invalid, inactive, or expired API Key', null, 401);
        }

        // IP Whitelist check
        if (!$apiKey->isIpAllowed($request->ip())) {
            return ApiResponse::error('IP address is not whitelisted for this API Key', null, 403);
        }

        $customer = $apiKey->customer;
        if (!$customer || $customer->status !== 'active') {
            return ApiResponse::error('Customer account is inactive or suspended', null, 403);
        }

        // Subscription check (Requirement #11 & #82: grace period check)
        $activeSubscription = $customer->activeSubscription;
        if (!$activeSubscription || !$activeSubscription->isActive()) {
            return ApiResponse::error('Active subscription required to access the API platform', null, 402);
        }

        // Update last used timestamp
        $apiKey->updateQuietly(['last_used_at' => now()]);

        // Attach to request attributes
        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('customer', $customer);

        return $next($request);
    }
}
