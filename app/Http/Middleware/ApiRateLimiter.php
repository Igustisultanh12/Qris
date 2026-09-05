<?php

namespace App\Http\Middleware;

use App\Http\Responses\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimiter
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->attributes->get('api_key');
        $customer = $request->attributes->get('customer');

        // Determine rate limit from api key, plan, or default
        $limit = $apiKey?->rate_limit_per_minute ?: 60;
        if ($customer?->activeSubscription?->plan?->rate_limit_per_minute) {
            $limit = $customer->activeSubscription->plan->rate_limit_per_minute;
        }

        $key = 'api_rate:' . ($apiKey ? $apiKey->id : $request->ip());

        if (RateLimiter::tooManyAttempts($key, $limit)) {
            $seconds = RateLimiter::availableIn($key);

            return ApiResponse::error(
                'Rate limit exceeded. Try again in ' . $seconds . ' seconds.',
                [
                    'retry_after_seconds' => $seconds,
                    'limit_per_minute' => $limit,
                ],
                429
            )->header('Retry-After', (string) $seconds);
        }

        RateLimiter::hit($key, 60);

        $response = $next($request);

        $remaining = RateLimiter::remaining($key, $limit);
        $response->headers->set('X-RateLimit-Limit', (string) $limit);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }
}
