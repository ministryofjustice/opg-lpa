<?php

declare(strict_types=1);

namespace Application\Listener;

use Laminas\EventManager\AbstractListenerAggregate;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;

/**
 * Redirects requests with a trailing slash to the same URL without it,
 * but only when the stripped URL matches a known route. Otherwise, the
 * normal 404 handling proceeds.
 */
class TrailingSlashRedirectListener extends AbstractListenerAggregate
{
    public function attach(EventManagerInterface $events, $priority = 1): void
    {
        $this->listeners[] = $events->attach(
            MvcEvent::EVENT_DISPATCH_ERROR,
            [$this, 'onDispatchError'],
            $priority
        );
    }

    public function onDispatchError(MvcEvent $event): void
    {
        // Only act on "route not found" errors
        if ($event->getError() !== Application::ERROR_ROUTER_NO_MATCH) {
            return;
        }

        $request = $event->getRequest();

        if (!$request instanceof HttpRequest) {
            return;
        }

        $uri = $request->getUri();
        $path = $uri->getPath() ?? '';

        // Only act if the path has a trailing slash and is not just "/"
        if ($path === '/' || !str_ends_with($path, '/')) {
            return;
        }

        $strippedPath = rtrim($path, '/');

        $router = $event->getRouter();

        $testUri = clone $uri;
        $testUri->setPath($strippedPath);

        $testRequest = clone $request;
        $testRequest->setUri($testUri);

        $routeMatch = $router->match($testRequest);

        if ($routeMatch === null) {
            return;
        }

        $redirectUrl = $strippedPath;

        /** @var string|null $query */
        $query = $uri->getQuery();
        if (is_string($query) && $query !== '') {
            $redirectUrl .= '?' . $query;
        }

        /** @var Response $response */
        $response = $event->getResponse();
        $response->getHeaders()->addHeaderLine('Location', $redirectUrl);
        $response->setStatusCode(301);

        $event->setResult($response);
        $event->stopPropagation();
    }
}
