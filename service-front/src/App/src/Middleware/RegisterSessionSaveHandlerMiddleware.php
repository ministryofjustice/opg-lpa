<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Registers the Redis-backed session save handler with PHP and applies native
 * session ini settings (name, cookie flags, GC) before Mezzio's SessionMiddleware
 * starts the session.
 *
 * This is the Mezzio equivalent of the legacy NativeSessionConfig::configure().
 * Must be piped immediately before SessionMiddleware in the pipeline.
 */
class RegisterSessionSaveHandlerMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly \SessionHandlerInterface $saveHandler,
        private readonly array $settings = [],
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!empty($this->settings['name'])) {
            session_name($this->settings['name']);
        }

        if (isset($this->settings['cookie_secure'])) {
            ini_set('session.cookie_secure', (string)(int) $this->settings['cookie_secure']);
        }

        if (isset($this->settings['cookie_httponly'])) {
            ini_set('session.cookie_httponly', (string)(int) $this->settings['cookie_httponly']);
        }

        if (isset($this->settings['gc_probability'])) {
            ini_set('session.gc_probability', (string)(int) $this->settings['gc_probability']);
            ini_set('session.gc_divisor', '100');
        }

        // Apply a reasonable default if not already configured
        if ('' === (string) ini_get('session.cookie_samesite')) {
            ini_set('session.cookie_samesite', 'Lax');
        }

        session_set_save_handler($this->saveHandler, true);

        return $handler->handle($request);
    }
}
