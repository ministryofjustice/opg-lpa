<?php

declare(strict_types=1);

use App\Handler\DashboardHandler;
use App\Handler\HomeHandler;
use App\Handler\LoginHandler;
use App\Handler\LogoutHandler;
use App\Handler\Lpa\CreateLpaHandler;
use App\Handler\Lpa\DonorIndexHandler;
use App\Handler\LpaTypeHandler;
use App\Handler\SessionExpiryHandler;
use App\Handler\TypeHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Middleware\LpaLoaderMiddleware;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', HomeHandler::class, 'root')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/home', HomeHandler::class, 'application.home')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/login[/{state}]', LoginHandler::class, ['GET', 'POST'], 'application.login')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/logout', LogoutHandler::class, 'application.logout')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/session-state', SessionExpiryHandler::class, 'session-state')
        ->setOptions(['unauthenticated_route' => true]);

    $app->route(
        '/user/dashboard',
        $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class),
        ['GET'],
        'user/dashboard',
    );
    $app->route(
        '/user/dashboard/page/{page:\d+}',
        $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class),
        ['GET'],
        'user/dashboard/pagination',
    );
    $app->route(
        '/user/dashboard/create[/{lpa-id:\d+}]',
        $factory->pipeline(LpaLoaderMiddleware::class, CreateLpaHandler::class),
        ['GET', 'POST'],
        'user/dashboard/create-lpa',
    );

    $app->route(
        '/lpa/type',
        $factory->pipeline(CsrfValidationMiddleware::class, LpaTypeHandler::class),
        ['GET', 'POST'],
        'lpa-type-no-id',
    );
    $app->route(
        '/lpa/{lpa-id:\d+}/type',
        $factory->pipeline(LpaLoaderMiddleware::class, CsrfValidationMiddleware::class, TypeHandler::class),
        ['GET', 'POST'],
        'lpa/form-type',
    );
    $app->route(
        '/lpa/{lpa-id:\d+}/donor',
        $factory->pipeline(LpaLoaderMiddleware::class, DonorIndexHandler::class),
        ['GET'],
        'lpa/donor',
    );
};
