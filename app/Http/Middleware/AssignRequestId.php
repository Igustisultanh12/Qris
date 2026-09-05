<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AssignRequestId
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $incomingId = $request->header('X-Request-ID');

        // Validate format or generate fresh UUID
        $requestId = (!empty($incomingId) && strlen($incomingId) <= 64)
            ? $incomingId
            : (string) Str::uuid();

        $request->headers->set('X-Request-ID', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
