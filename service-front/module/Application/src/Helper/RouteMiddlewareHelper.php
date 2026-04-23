<?php

declare(strict_types=1);

namespace Application\Helper;

use Application\Middleware\AuthenticationMiddleware;
use Application\Middleware\IdentityTokenRefreshMiddleware;
use Application\Middleware\LpaLoaderMiddleware;
use Application\Middleware\RequestLoggingMiddleware;
use Application\Middleware\RouteMatchMiddleware;
use Application\Middleware\SessionBootstrapMiddleware;
use Application\Middleware\TermsAndConditionsMiddleware;
use Application\Middleware\UserDetailsMiddleware;
use Laminas\Mvc\Middleware\PipeSpec;

class RouteMiddlewareHelper
{
    /**
     * Middleware that runs on every non-ping request: attaches request context
     * to logs, bootstraps the session, and refreshes the identity token.
     * Must always come first in any PipeSpec.
     */
    private const array GLOBAL_MIDDLEWARE = [
        RequestLoggingMiddleware::class,
        SessionBootstrapMiddleware::class,
        IdentityTokenRefreshMiddleware::class,
    ];

    /**
     * Builds a PipeSpec for public (unauthenticated) routes.
     *
     * Includes the global middleware stack and RouteMatchMiddleware so that
     * downstream handlers can read the Mezzio RouteResult attribute, but
     * intentionally omits auth, user-details, T&C, and LPA loader checks.
     */
    public static function addPublicMiddleware(string $handlerClass): PipeSpec
    {
        return new PipeSpec(...[
            ...self::GLOBAL_MIDDLEWARE,
            RouteMatchMiddleware::class,
            $handlerClass,
        ]);
    }

    /**
     * Builds a PipeSpec with the standard authenticated middleware stack,
     * minus any classes in $ignore.
     *
     * @param string $handlerClass The handler to append at the end of the pipeline.
     * @param string[] $ignore Middleware classes to omit from the stack.
     */
    public static function addMiddleware(string $handlerClass, array $ignore = []): PipeSpec
    {
        $middlewares = array_diff([
            ...self::GLOBAL_MIDDLEWARE,
            RouteMatchMiddleware::class,
            AuthenticationMiddleware::class,
            UserDetailsMiddleware::class,
            TermsAndConditionsMiddleware::class,
            LpaLoaderMiddleware::class,
        ], $ignore);

        $middlewares[] = $handlerClass;

        return new PipeSpec(...$middlewares);
    }
}
