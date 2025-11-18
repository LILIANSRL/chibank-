<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Enterprise Health Check Controller
 * Provides comprehensive health check endpoints for monitoring and alerting
 */
class HealthCheckController extends Controller
{
    /**
     * Basic health check endpoint
     * Returns a simple 200 OK response
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function basic()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    /**
     * Detailed health check endpoint
     * Checks all critical services and dependencies
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function detailed()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $overall = array_reduce($checks, function ($carry, $check) {
            return $carry && $check['healthy'];
        }, true);

        $statusCode = $overall ? 200 : 503;

        return response()->json([
            'status' => $overall ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
            'application' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'version' => '5.0.0',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
        ], $statusCode);
    }

    /**
     * Check database connectivity
     *
     * @return array
     */
    private function checkDatabase()
    {
        try {
            DB::connection()->getPdo();
            $time = DB::select("SELECT 1")[0];
            
            return [
                'healthy' => true,
                'message' => 'Database connection successful',
                'latency_ms' => null,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Database connection failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check cache system
     *
     * @return array
     */
    private function checkCache()
    {
        try {
            $key = 'health_check_' . time();
            $value = 'test_value';
            
            Cache::put($key, $value, 60);
            $retrieved = Cache::get($key);
            Cache::forget($key);
            
            $healthy = $retrieved === $value;
            
            return [
                'healthy' => $healthy,
                'message' => $healthy ? 'Cache system operational' : 'Cache verification failed',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Cache system failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check storage system
     *
     * @return array
     */
    private function checkStorage()
    {
        try {
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test';
            
            Storage::put($testFile, $testContent);
            $exists = Storage::exists($testFile);
            $content = Storage::get($testFile);
            Storage::delete($testFile);
            
            $healthy = $exists && $content === $testContent;
            
            return [
                'healthy' => $healthy,
                'message' => $healthy ? 'Storage system operational' : 'Storage verification failed',
                'driver' => config('filesystems.default'),
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Storage system failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check queue system
     *
     * @return array
     */
    private function checkQueue()
    {
        try {
            $connection = config('queue.default');
            
            return [
                'healthy' => true,
                'message' => 'Queue system configured',
                'connection' => $connection,
            ];
        } catch (\Exception $e) {
            return [
                'healthy' => false,
                'message' => 'Queue system check failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get application metrics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function metrics()
    {
        return response()->json([
            'timestamp' => now()->toIso8601String(),
            'system' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'uptime_seconds' => null, // Can be tracked with a custom solution
            ],
            'application' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'debug_mode' => config('app.debug'),
                'timezone' => config('app.timezone'),
            ],
        ], 200);
    }

    /**
     * Readiness check - determines if application can serve traffic
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function readiness()
    {
        $ready = $this->checkDatabase()['healthy'];

        return response()->json([
            'ready' => $ready,
            'timestamp' => now()->toIso8601String(),
        ], $ready ? 200 : 503);
    }

    /**
     * Liveness check - determines if application is alive
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveness()
    {
        return response()->json([
            'alive' => true,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }
}
