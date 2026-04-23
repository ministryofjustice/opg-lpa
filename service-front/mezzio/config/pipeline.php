<?php

/**
 * Global middleware pipeline.
 *
 * Order is significant. Each entry maps to a concern that was previously
 * handled inside Module::onBootstrap() in the Laminas MVC app.
 *
 * AuthenticationMiddleware, UserDetailsMiddleware, TermsAndConditionsMiddleware,
 * and LpaLoaderMiddleware are NOT piped globally here — they are applied
 * per-route in routes.php via $factory->pipeline(...).
 */

declare(strict_types=1);

use Application\Middleware\RequestLoggingMiddleware;
use Application\Middleware\SessionBootstrapMiddleware;
use Application\Middleware\TrailingSlashMiddleware;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Helper\ServerUrlMiddleware;
use Mezzio\Helper\UrlHelperMiddleware;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Psr\Container\ContainerInterface;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    // -------------------------------------------------------------------------
    // 1. Error handling — outermost layer, catches everything.
    //    Replaces Module::handleError() (MVC dispatch/render error events).
    // -------------------------------------------------------------------------
    $app->pipe(ErrorHandler::class);

    // -------------------------------------------------------------------------
    // 2. Trailing-slash redirect — must run before routing so FastRoute never
    //    sees the slash. Replaces TrailingSlashRedirectListener.
    // -------------------------------------------------------------------------
    $app->pipe(TrailingSlashMiddleware::class);

    // -------------------------------------------------------------------------
    // 3. Request logging — pushes path, method, and trace-ID onto the Monolog
    //    processor stack for all subsequent log calls in this request.
    //    Replaces the logger->pushProcessor() block in Module::onBootstrap().
    // -------------------------------------------------------------------------
    $app->pipe(RequestLoggingMiddleware::class);

    // -------------------------------------------------------------------------
    // 4. Session bootstrap — configures the Redis save handler, starts the
    //    laminas-session SessionManager, and regenerates the session ID on first
    //    visit. Skipped for /ping/elb and /ping/json.
    //    Replaces Module::bootstrapSession().
    // -------------------------------------------------------------------------
    $app->pipe(SessionBootstrapMiddleware::class);

    // -------------------------------------------------------------------------
    // 5. Identity token refresh — checks the authenticated user's API token on
    //    every request and updates or clears the session identity accordingly.
    //    Skipped for /ping/elb, /ping/json, and /session-state.
    //    Replaces Module::bootstrapIdentity().
    // -------------------------------------------------------------------------
    $app->pipe(IdentityTokenRefreshMiddleware::class);

    // -------------------------------------------------------------------------
    // 6. Server URL helper — populates the ServerUrlHelper with the current
    //    request's scheme and host so templates can build absolute URLs.
    // -------------------------------------------------------------------------
    $app->pipe(ServerUrlMiddleware::class);

    // -------------------------------------------------------------------------
    // 7. Routing — matches the request path to a registered route and stores
    //    the RouteResult as a request attribute for downstream middleware.
    // -------------------------------------------------------------------------
    $app->pipe(RouteMiddleware::class);

    // -------------------------------------------------------------------------
    // 8. Implicit method handling and method-not-allowed responses.
    //    Order matters: ImplicitHead and ImplicitOptions must precede
    //    MethodNotAllowed.
    // -------------------------------------------------------------------------
    $app->pipe(ImplicitHeadMiddleware::class);
    $app->pipe(ImplicitOptionsMiddleware::class);
    $app->pipe(MethodNotAllowedMiddleware::class);

    // -------------------------------------------------------------------------
    // 9. URL helper — seeds UrlHelper with the matched route result so handlers
    //    and middleware can call $urlHelper->generate('route-name').
    // -------------------------------------------------------------------------
    $app->pipe(UrlHelperMiddleware::class);

    // -------------------------------------------------------------------------
    // 10. Dispatch — invokes the matched handler (or per-route middleware
    //     pipeline). Route-scoped middleware (Auth, UserDetails, T&C,
    //     LpaLoader) is applied here via $factory->pipeline() in routes.php.
    // -------------------------------------------------------------------------
    $app->pipe(DispatchMiddleware::class);

    // -------------------------------------------------------------------------
    // 11. 404 fallback.
    // -------------------------------------------------------------------------
    $app->pipe(NotFoundHandler::class);
};
