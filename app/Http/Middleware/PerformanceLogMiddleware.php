<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class PerformanceLogMiddleware
{
    /**
     * Handle an incoming request and log duration + query count for performance debugging.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.debug')) {
            return $next($request);
        }

        $start = microtime(true);
        DB::enableQueryLog();

        $response = $next($request);

        $durationMs = round((microtime(true) - $start) * 1000, 2);
        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        $slowCount = 0;
        $slowThresholdMs = 100;
        foreach ($queries as $query) {
            $timeMs = $query['time'] ?? 0;
            if ($timeMs >= $slowThresholdMs) {
                $slowCount++;
            }
        }

        $logPath = storage_path('logs/performance-debug.log');
        $entry = json_encode([
            'path' => $request->path(),
            'method' => $request->method(),
            'duration_ms' => $durationMs,
            'query_count' => $queryCount,
            'slow_queries_count' => $slowCount,
            'timestamp' => now()->toIso8601String(),
        ])."\n";
        @file_put_contents($logPath, $entry, FILE_APPEND | LOCK_EX);

        return $response;
    }
}
