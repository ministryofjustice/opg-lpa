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

        $response = $handler->handle($request);

        $after    = getrusage();
        $wallMs   = round((microtime(true) - $wallStart) * 1000, 2);
        $userMs   = $this->diffMs($before, $after, 'ru_utime.tv_sec', 'ru_utime.tv_usec');
        $systemMs = $this->diffMs($before, $after, 'ru_stime.tv_sec', 'ru_stime.tv_usec');
        $cpuMs    = $userMs + $systemMs;
        $load     = sys_getloadavg();

        $this->getLogger()->info('request cpu usage', [
            'wall_ms'        => $wallMs,
            'cpu_total_ms'   => $cpuMs,
            'cpu_user_ms'    => $userMs,
            'cpu_system_ms'  => $systemMs,
            // Large wait_ms = request was blocked on I/O (session lock, API calls, Redis)
            // Large cpu_ms relative to wall_ms = request is CPU-bound
            'wait_ms'        => round($wallMs - $cpuMs, 2),
            'load_avg_1min'  => $load[0],
            'load_avg_5min'  => $load[1],
            'load_avg_15min' => $load[2],
            'path'           => $request->getUri()->getPath(),
            'method'         => $request->getMethod(),
            'status'         => $response->getStatusCode(),
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
