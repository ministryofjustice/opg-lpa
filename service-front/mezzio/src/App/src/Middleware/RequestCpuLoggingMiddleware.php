<?php

declare(strict_types=1);

namespace App\Middleware;

use MakeShared\Logging\LoggerTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;

/**
 * Logs per-request CPU time and system load average at the end of each request.
 *
 * Uses getrusage() for the CPU time consumed by this PHP-FPM worker during the
 * request, and sys_getloadavg() for the 1-minute system load average — which
 * correlates with what ECS CPU utilisation metrics report.
 *
 * Pipe immediately after ErrorHandler so it wraps the entire request lifecycle.
 */
class RequestCpuLoggingMiddleware implements MiddlewareInterface, LoggerAwareInterface
{
    use LoggerTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $wallStart = microtime(true);
        $before    = getrusage();

        // Blocks here until the entire downstream pipeline (all remaining middleware + route handler)
        // has completed and returned a response. PHP-FPM is synchronous — nothing escapes this call.
        $response = $handler->handle($request);

        $after    = getrusage();
        $wallMs   = round((microtime(true) - $wallStart) * 1000, 2);
        $userMs   = $this->diffMs($before, $after, 'ru_utime.tv_sec', 'ru_utime.tv_usec');
        $systemMs = $this->diffMs($before, $after, 'ru_stime.tv_sec', 'ru_stime.tv_usec');
        $cpuMs    = $userMs + $systemMs;
        $load     = sys_getloadavg();

        $waitMs = round($wallMs - $cpuMs, 2);
        // cpu_pct: % of wall-clock time this worker was on CPU.
        // ~100% = CPU-bound (e.g. Twig compile). ~0-10% = I/O-bound (session lock, API calls).
        $cpuPct = $wallMs > 0 ? round(($cpuMs / $wallMs) * 100, 1) : 0.0;

        $this->getLogger()->info('request timing', [
            // HTTP request identity
            'method'        => $request->getMethod(),
            'path'          => $request->getUri()->getPath(),
            'status'        => $response->getStatusCode(),

            // Total wall-clock time from first byte to last byte of response
            'duration_ms'   => $wallMs,

            // Total CPU time consumed by this PHP-FPM worker (app + kernel combined).
            // High cpu_ms relative to duration_ms = CPU-bound work (e.g. Twig compiling templates).
            'cpu_ms'        => $cpuMs,

            // CPU as a % of wall-clock time. ~100% = CPU-bound. ~0-10% = mostly waiting on I/O.
            'cpu_percent'   => $cpuPct,

            // CPU time spent executing PHP application code (business logic, Twig rendering, etc.)
            'app_cpu_ms'    => $userMs,

            // CPU time spent in kernel on behalf of this request (file I/O, sockets, memory allocation)
            'kernel_cpu_ms' => $systemMs,

            // Wall time not accounted for by CPU — the worker was blocked waiting on something external.
            // High io_wait_ms = session lock contention, slow API calls, or Redis latency.
            'io_wait_ms'    => $waitMs,

            // System load averages — correlate with ECS CPU utilisation metrics in CloudWatch
            'load_1m'       => $load[0],
            'load_5m'       => $load[1],
            'load_15m'      => $load[2],
        ]);

        return $response;
    }

    private function diffMs(array $before, array $after, string $secKey, string $usecKey): float
    {
        $sec  = ($after[$secKey]  ?? 0) - ($before[$secKey]  ?? 0);
        $usec = ($after[$usecKey] ?? 0) - ($before[$usecKey] ?? 0);

        return round(($sec * 1000) + ($usec / 1000), 2);
    }
}
