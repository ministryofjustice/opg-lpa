<?php

declare(strict_types=1);

namespace Application\Middleware;

use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Redirects any request whose path ends with a trailing slash (other than "/")
 * to the same URL with the slash removed.
 *
 * Replaces the MVC TrailingSlashRedirectListener. In Mezzio we don't need to
 * verify the stripped path matches a route first — FastRoute will return a 404
 * for genuinely unknown routes after the redirect anyway.
 *
 * Must run before RouteMiddleware so the redirect happens before routing.
 */
class TrailingSlashMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri  = $request->getUri();
        $path = $uri->getPath();

        if ($path !== '/' && str_ends_with($path, '/')) {
            $strippedUri = $uri->withPath(rtrim($path, '/'));
            return new RedirectResponse((string) $strippedUri, 301);
        }

        return $handler->handle($request);
    }
}
