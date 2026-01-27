<?php

/**
 * Setup routes with a single request method:
 *
 * $app->get('/', App\Handler\HomePageHandler::class, 'home');
 * $app->post('/album', App\Handler\AlbumCreateHandler::class, 'album.create');
 * $app->put('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.put');
 * $app->patch('/album/:id', App\Handler\AlbumUpdateHandler::class, 'album.patch');
 * $app->delete('/album/:id', App\Handler\AlbumDeleteHandler::class, 'album.delete');
 *
 * Or with multiple request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class, ['GET', 'POST', ...], 'contact');
 *
 * Or handling all request methods:
 *
 * $app->route('/contact', App\Handler\ContactHandler::class)->setName('contact');
 *
 * or:
 *
 * $app->route(
 *     '/contact',
 *     App\Handler\ContactHandler::class,
 *     Mezzio\Router\Route::HTTP_METHOD_ANY,
 *     'contact'
 * );
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;

return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomeHandler::class, 'home');
    $app->route('/sign-in', App\Handler\SignInHandler::class, ['GET', 'POST'], 'sign.in');
    $app->get('/sign-out', App\Handler\SignOutHandler::class, 'sign.out');
    $app->route('/system-message', App\Handler\SystemMessageHandler::class, ['GET', 'POST'], 'system.message');
    $app->route('/feedback', App\Handler\FeedbackHandler::class, ['GET', 'POST'], 'feedback');
    $app->route('/user-search', App\Handler\UserSearchHandler::class, ['GET', 'POST'], 'user.search');
    $app->route('/user-find', App\Handler\UserFindHandler::class, ['GET', 'POST'], 'user.find');
    $app->get('/user/{id}/lpas', App\Handler\UserLpasHandler::class, 'user.lpas');
};
