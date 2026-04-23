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
 * Replicates the bootstrapSession() logic from Module::onBootstrap(), converted
 * to PSR-15 middleware. Skipped for health-check endpoints that must not create
 * sessions.
 *
 * Must run before any middleware that reads or writes the session (e.g.
 * IdentityTokenRefreshMiddleware, AuthenticationMiddleware).
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
            // Configure the save handler (Redis), cookie settings, etc.
            $this->nativeSessionConfig->configure();

            // Start the session and make this manager the default for all Containers.
            $this->sessionManager->start();
            Container::setDefaultManager($this->sessionManager);

            // Regenerate session ID on first visit to prevent fixation.
            $this->sessionManagerSupport->initialise();
        }

        return $handler->handle($request);
    }
}
