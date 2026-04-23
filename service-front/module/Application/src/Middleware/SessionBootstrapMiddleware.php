<?php

declare(strict_types=1);

namespace Application\Middleware;

use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Session\SessionManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Bootstraps the laminas-session stack on every request that requires a session.
 *
 * NativeSessionConfig::configure() and Container::setDefaultManager() are called
 * earlier, in Module::onBootstrap(), so that the Redis save handler is registered
 * before any Laminas Container subclass (e.g. authentication storage) is constructed
 * during service wiring.  This middleware is responsible only for starting the
 * session (if it has not already been started by a Container constructor) and
 * running the session-ID regeneration logic.
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
        private readonly SessionManager $sessionManager,
        private readonly SessionManagerSupport $sessionManagerSupport,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if (!in_array($path, self::SESSION_EXCLUDED_PATHS, true)) {
            // configure() and Container::setDefaultManager() are called early in
            // Module::onBootstrap() so that the correct save handler is registered
            // before any Laminas\Session\Container subclass (e.g. authentication
            // storage) is instantiated during service wiring.
            // Here we only need to ensure the session is open and initialised.
            if (PHP_SESSION_NONE === session_status()) {
                $this->sessionManager->start();
            }

            $this->sessionManagerSupport->initialise();
        }

        return $handler->handle($request);
    }
}
