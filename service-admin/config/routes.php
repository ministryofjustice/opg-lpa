<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use MakeShared\Handler\PingHandlerElb;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomeHandler::class, 'home');
    $app->route('/sign-in', App\Handler\SignInHandler::class, ['GET', 'POST'], 'sign.in')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/ping/elb', PingHandlerElb::class, 'ping.elb')
        ->setOptions(['unauthenticated_route' => true]);
    $app->get('/sign-out', App\Handler\SignOutHandler::class, 'sign.out');
    $app->route('/system-message', App\Handler\SystemMessageHandler::class, ['GET', 'POST'], 'system.message');
    $app->route('/feedback', App\Handler\FeedbackHandler::class, ['GET', 'POST'], 'feedback');
    $app->route('/user-search', App\Handler\UserSearchHandler::class, ['GET', 'POST'], 'user.search');
    $app->route('/user-find', App\Handler\UserFindHandler::class, ['GET', 'POST'], 'user.find');
    $app->get('/user/{id}/lpas', App\Handler\UserLpasHandler::class, 'user.lpas');
};
