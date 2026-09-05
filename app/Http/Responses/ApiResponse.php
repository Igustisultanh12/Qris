<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        array $meta = [],
        int $statusCode = 200
    ): JsonResponse {
        $requestId = request()->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
            'request_id' => $requestId,
        ], $statusCode)->header('X-Request-ID', $requestId);
    }

    public static function paginated(
        mixed $paginator,
        string $message = 'Success',
        int $statusCode = 200
    ): JsonResponse {
        $requestId = request()->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'request_id' => $requestId,
        ], $statusCode)->header('X-Request-ID', $requestId);
    }

    public static function error(
        string $message = 'An error occurred',
        mixed $errors = null,
        int $statusCode = 400
    ): JsonResponse {
        $requestId = request()->header('X-Request-ID') ?? (string) \Illuminate\Support\Str::uuid();

        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?? new \stdClass(),
            'request_id' => $requestId,
        ], $statusCode)->header('X-Request-ID', $requestId);
    }
}
