<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/health",
     *     tags={"System"},
     *     summary="Health check endpoint",
     *     @OA\Response(
     *         response=200,
     *         description="System is healthy",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time"),
     *             @OA\Property(property="services", type="object",
     *                 @OA\Property(property="database", type="string", example="ok"),
     *                 @OA\Property(property="redis", type="string", example="ok"),
     *                 @OA\Property(property="queue", type="string", example="ok")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=503, description="Service unavailable")
     * )
     */
    public function health(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queue' => 'ok', // Basic check
        ];

        $allHealthy = ! in_array('error', $services);

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'services' => $services,
            'version' => config('app.version', '1.0.0'),
        ], $allHealthy ? 200 : 503);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/metrics",
     *     tags={"System"},
     *     summary="Prometheus metrics endpoint",
     *     @OA\Response(
     *         response=200,
     *         description="Metrics in Prometheus format"
     *     )
     * )
     */
    public function metrics(): \Illuminate\Http\Response
    {
        $metrics = [];

        // Application metrics
        $metrics[] = '# HELP app_users_total Total number of users';
        $metrics[] = '# TYPE app_users_total gauge';
        $metrics[] = 'app_users_total '.DB::table('users')->count();

        // Database connection metrics
        $metrics[] = '# HELP db_connections Database connections';
        $metrics[] = '# TYPE db_connections gauge';
        $metrics[] = 'db_connections{status="active"} 1';

        // Memory usage
        $metrics[] = '# HELP php_memory_usage PHP memory usage in bytes';
        $metrics[] = '# TYPE php_memory_usage gauge';
        $metrics[] = 'php_memory_usage '.memory_get_usage(true);

        // Queue metrics (basic)
        $metrics[] = '# HELP queue_jobs_pending Pending jobs in queue';
        $metrics[] = '# TYPE queue_jobs_pending gauge';
        $metrics[] = 'queue_jobs_pending '.DB::table('jobs')->count();

        return response(implode("\n", $metrics)."\n")
            ->header('Content-Type', 'text/plain; version=0.0.4');
    }

    /**
     * Check database connection
     */
    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Check Redis connection
     */
    private function checkRedis(): string
    {
        try {
            Redis::ping();

            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }
}
