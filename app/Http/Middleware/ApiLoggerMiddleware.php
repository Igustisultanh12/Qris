<?php

namespace App\Http\Middleware;

use App\Models\ApiUsageLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggerMiddleware
{
    /**
     * Handle an incoming request and log its metadata.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $startTime) * 1000);
        $requestId = $response->headers->get('X-Request-ID') ?: $request->header('X-Request-ID');

        $apiKey = $request->attributes->get('api_key');
        $customer = $request->attributes->get('customer');

        // Mask sensitive payload fields
        $body = $request->except(['password', 'secret', 'api_secret', 'token', 'access_token']);

        // Mask headers
        $headers = collect($request->headers->all())->except(['authorization', 'x-api-key', 'cookie'])->toArray();

        // Response preview (limited)
        $responseContent = null;
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $responseContent = $response->getData(true);
        }

        try {
            ApiUsageLog::create([
                'request_id' => $requestId ?: (string) \Illuminate\Support\Str::uuid(),
                'customer_id' => $customer?->id,
                'api_key_id' => $apiKey?->id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'request_headers' => $headers,
                'request_body' => $body,
                'response_status' => $response->getStatusCode(),
                'response_body' => is_array($responseContent) ? array_intersect_key($responseContent, array_flip(['success', 'message', 'meta', 'request_id'])) : null,
                'duration_ms' => $durationMs,
                'error_message' => $response->isClientError() || $response->isServerError() ? ($responseContent['message'] ?? null) : null,
            ]);
        } catch (\Throwable) {
            // Fail silently so logging never breaks request lifecycle
        }

        return $response;
    }
}
