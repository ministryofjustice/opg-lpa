<?php

declare(strict_types=1);

use App\Handler\HomeHandler;
use App\Handler\HomePageHandler;
use App\Handler\PingHandler;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

return static function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', HomePageHandler::class, 'home');
    $app->get('/api/ping', PingHandler::class, 'api.ping');
    $app->get('/home', HomeHandler::class, 'application.home');
};
