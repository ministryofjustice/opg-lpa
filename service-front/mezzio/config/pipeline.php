<?php

declare(strict_types=1);

use App\Middleware\AuthenticationMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Middleware\PersistentSessionDetailsMiddleware;
use App\Middleware\UserDetailsMiddleware;
use Mezzio\Csrf\CsrfMiddleware;
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
use Mezzio\Session\SessionMiddleware;
use Psr\Container\ContainerInterface;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->pipe(ErrorHandler::class);
    $app->pipe(ServerUrlMiddleware::class);
    $app->pipe(SessionMiddleware::class);
    $app->pipe(CsrfMiddleware::class);
    $app->pipe(IdentityTokenRefreshMiddleware::class);
    $app->pipe(RouteMiddleware::class);
    $app->pipe(ImplicitHeadMiddleware::class);
    $app->pipe(ImplicitOptionsMiddleware::class);
    $app->pipe(MethodNotAllowedMiddleware::class);
    $app->pipe(UrlHelperMiddleware::class);
    $app->pipe(PersistentSessionDetailsMiddleware::class);
    $app->pipe(AuthenticationMiddleware::class);
    $app->pipe(UserDetailsMiddleware::class);
    $app->pipe(DispatchMiddleware::class);
    $app->pipe(NotFoundHandler::class);
};
