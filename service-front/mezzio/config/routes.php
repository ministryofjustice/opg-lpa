<?php

declare(strict_types=1);

use App\Handler\AboutYouHandler;
use App\Handler\ChangePasswordHandler;
use App\Handler\DashboardHandler;
use App\Handler\HomeHandler;
use App\Handler\LoginHandler;
use App\Handler\LogoutHandler;
use App\Handler\ConfirmRegistrationHandler;
use App\Handler\ForgotPasswordHandler;
use App\Handler\RegisterHandler;
use App\Handler\ResendActivationEmailHandler;
use App\Handler\ResetPasswordHandler;
use App\Handler\Lpa\CreateLpaHandler;
use App\Handler\Lpa\DonorIndexHandler;
use App\Handler\LpaTypeHandler;
use App\Handler\SessionExpiryHandler;
use App\Handler\TypeHandler;
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

    $app->route('/signup', RegisterHandler::class, ['GET', 'POST'], 'register')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/signup/confirm/{token:[a-zA-Z0-9]+}', ConfirmRegistrationHandler::class, 'register/confirm')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route('/signup/resend-email', ResendActivationEmailHandler::class, ['GET', 'POST'], 'register/resend-email')
        ->setOptions(['unauthenticated_route' => true]);

    $app->route('/forgot-password', ForgotPasswordHandler::class, ['GET', 'POST'], 'forgot-password')
        ->setOptions(['unauthenticated_route' => true]);
    $app->route(
        '/forgot-password/reset/{token:[a-zA-Z0-9]+}',
        ResetPasswordHandler::class,
        ['GET', 'POST'],
        'forgot-password/callback',
    )->setOptions(['unauthenticated_route' => true]);

    $app->get('/user/dashboard', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard');
    $app->get('/user/dashboard/page/{page:\d+}', $factory->pipeline(LpaLoaderMiddleware::class, DashboardHandler::class), 'user/dashboard/pagination');
    $app->route('/user/dashboard/create[/{lpa-id:\d+}]', $factory->pipeline(LpaLoaderMiddleware::class, CreateLpaHandler::class), ['GET', 'POST'], 'user/dashboard/create-lpa');
    $app->route('/user/about-you[/{new}]', AboutYouHandler::class, ['GET', 'POST'], 'user/about-you')
        ->setOptions(['allowIncompleteUser' => true]);

    $app->route('/user/change-password', ChangePasswordHandler::class, ['GET', 'POST'], 'user/change-password');

    $app->route('/lpa/type', LpaTypeHandler::class, ['GET', 'POST'], 'lpa-type-no-id');
    $app->route('/lpa/{lpa-id:\d+}/type', $factory->pipeline(LpaLoaderMiddleware::class, TypeHandler::class), ['GET', 'POST'], 'lpa/form-type');
    $app->get('/lpa/{lpa-id:\d+}/donor', $factory->pipeline(LpaLoaderMiddleware::class, DonorIndexHandler::class), 'lpa/donor');
};
