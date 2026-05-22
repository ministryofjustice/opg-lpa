<?php

declare(strict_types=1);

use App\Handler\HomeHandler;
use App\Handler\LoginHandler;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', HomeHandler::class, 'root');
    $app->get('/home', HomeHandler::class, 'application.home');
    $app->route('/login[/{state}]', LoginHandler::class, ['GET', 'POST'], 'application.login');
};
