<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Info(
 *     title="Domestika Laravel API",
 *     version="1.0.0",
 *     description="Enterprise Laravel API with JWT Authentication, RBAC, and full observability",
 *     @OA\Contact(
 *         email="api@domestika.local"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearer_token",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT Authorization header using the Bearer scheme"
 * )
 */
class BaseController extends Controller
{
    use AuthorizesRequests;
    /**
     * Success response helper
     */
    protected function success(mixed $data = null, string $message = 'Success', int $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'timestamp' => now()->toIso8601String(),
        ], $code);
    }

    /**
     * Error response helper (RFC 7807 Problem+JSON)
     */
    protected function error(string $title, string $detail = '', int $status = 400, ?string $type = null, mixed $additional = []): JsonResponse
    {
        $problem = [
            'type' => $type ?? 'about:blank',
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
            'instance' => request()->path(),
            'timestamp' => now()->toIso8601String(),
        ];

        if (! empty($additional)) {
            $problem = array_merge($problem, $additional);
        }

        return response()->json($problem, $status)
            ->header('Content-Type', 'application/problem+json');
    }

    /**
     * Get correlation ID from request
     */
    protected function getCorrelationId(): string
    {
        return request()->header('X-Correlation-ID', (string) \Illuminate\Support\Str::uuid());
    }
}
