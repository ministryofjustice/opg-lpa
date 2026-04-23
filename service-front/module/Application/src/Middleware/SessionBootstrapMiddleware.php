<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Session\NativeSessionConfig;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bootstraps the laminas-session stack on every request that requires a session.
 *
 * In a pure Mezzio pipeline this middleware is responsible for the full
 * session setup: configure (save handler, cookie settings) → start → initialise.
 *
 * In the Laminas MVC app Module::onBootstrap() calls
 * NativeSessionConfig::configure() and Container::setDefaultManager() early
 * so that the correct save handler is in place before any Container subclass
 * (e.g. Laminas\Authentication\Storage\Session) is constructed during service
 * wiring.  When that early setup has already run, configure() is a no-op here
 * because session_status() will be PHP_SESSION_ACTIVE.
 *
 * Skipped entirely for health-check endpoints that must not create sessions.
 */
class SessionBootstrapMiddleware implements MiddlewareInterface
{
    /**
     * Paths that must not start a session (health checks, elb ping etc.).
     */
    private const array SESSION_EXCLUDED_PATHS = [
        '/ping/elb',
        '/ping/json',
    ];

    public function __construct(
        private readonly NativeSessionConfig $nativeSessionConfig,
        private readonly SessionManager $sessionManager,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!in_array($path, self::SESSION_EXCLUDED_PATHS, true)) {
            // configure() sets the save handler and ini settings.
            // In the MVC app this has already been called in Module::onBootstrap(),
            // so session_status() will be PHP_SESSION_ACTIVE and the call is a no-op.
            // In a pure Mezzio pipeline it runs here for the first time.
            if (PHP_SESSION_NONE === session_status()) {
                $this->nativeSessionConfig->configure();
                $this->sessionManager->start();
                Container::setDefaultManager($this->sessionManager);
            }

            $this->sessionManagerSupport->initialise();
        }

        return $handler->handle($request);
    }
}
