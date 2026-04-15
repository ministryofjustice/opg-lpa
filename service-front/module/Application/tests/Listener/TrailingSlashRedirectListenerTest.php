<?php

declare(strict_types=1);

namespace ApplicationTest\Listener;

use Application\Listener\TrailingSlashRedirectListener;
use Laminas\EventManager\EventManagerInterface;
use Laminas\Http\PhpEnvironment\Request as HttpRequest;
use Laminas\Http\Response;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\Router\Http\RouteMatch;
use Laminas\Router\RouteStackInterface;
use Laminas\Uri\Http as HttpUri;
use PHPUnit\Framework\TestCase;

class TrailingSlashRedirectListenerTest extends TestCase
{
    private TrailingSlashRedirectListener $listener;

    protected function setUp(): void
    {
        $this->listener = new TrailingSlashRedirectListener();
    }

    public function testAttachRegistersDispatchErrorListener(): void
    {
        $events = $this->createMock(EventManagerInterface::class);
        $events->expects($this->once())
            ->method('attach')
            ->with(MvcEvent::EVENT_DISPATCH_ERROR, [$this->listener, 'onDispatchError'], 100);

        $this->listener->attach($events, 100);
    }

    public function testIgnoresNonRouteErrors(): void
    {
        $event = new MvcEvent();
        $event->setError(Application::ERROR_EXCEPTION);

        $response = new Response();
        $event->setResponse($response);

        $this->listener->onDispatchError($event);

        // Should not have been modified (no Location header, no 301)
        $this->assertFalse($response->getHeaders()->has('Location'));
    }

    public function testIgnoresRootPath(): void
    {
        $event = $this->createRouteNotMatchEvent('/');

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertFalse($response->getHeaders()->has('Location'));
    }

    public function testIgnoresPathWithoutTrailingSlash(): void
    {
        $event = $this->createRouteNotMatchEvent('/login');

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertFalse($response->getHeaders()->has('Location'));
    }

    public function testRedirectsWhenStrippedPathMatchesKnownRoute(): void
    {
        $event = $this->createRouteNotMatchEvent('/login/');

        // The router should match the stripped path
        $router = $this->createMock(RouteStackInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->willReturn(new RouteMatch([]));
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()->get('Location')->getFieldValue());
        $this->assertTrue($event->propagationIsStopped());
    }

    public function testDoesNotRedirectWhenStrippedPathDoesNotMatch(): void
    {
        $event = $this->createRouteNotMatchEvent('/nonexistent-page/');

        $router = $this->createMock(RouteStackInterface::class);
        $router->expects($this->once())
            ->method('match')
            ->willReturn(null);
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertFalse($response->getHeaders()->has('Location'));
        $this->assertFalse($event->propagationIsStopped());
    }

    public function testPreservesQueryStringOnRedirect(): void
    {
        $event = $this->createRouteNotMatchEvent('/user/dashboard/', 'page=2&sort=date');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            '/user/dashboard?page=2&sort=date',
            $response->getHeaders()->get('Location')->getFieldValue()
        );
    }

    public function testRedirectsDeepNestedPaths(): void
    {
        $event = $this->createRouteNotMatchEvent('/lpa/12345/primary-attorney/');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame(
            '/lpa/12345/primary-attorney',
            $response->getHeaders()->get('Location')->getFieldValue()
        );
    }

    public function testDoesNotRedirectMultipleTrailingSlashesWhenNoRouteMatch(): void
    {
        $event = $this->createRouteNotMatchEvent('/login///');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->willReturn(null);
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertFalse($response->getHeaders()->has('Location'));
    }

    public function testRedirectsMultipleTrailingSlashesWhenStrippedRouteMatches(): void
    {
        $event = $this->createRouteNotMatchEvent('/login///');

        $router = $this->createMock(RouteStackInterface::class);
        $router->method('match')->willReturn(new RouteMatch([]));
        $event->setRouter($router);

        $this->listener->onDispatchError($event);

        /** @var Response $response */
        $response = $event->getResponse();
        $this->assertSame(301, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaders()->get('Location')->getFieldValue());
    }

    /**
     * Helper to create an MvcEvent simulating a route-not-found dispatch error.
     */
    private function createRouteNotMatchEvent(string $path, string $query = ''): MvcEvent
    {
        $uri = new HttpUri();
        $uri->setPath($path);
        if ($query !== '') {
            $uri->setQuery($query);
        }

        $request = new HttpRequest();
        $request->setUri($uri);

        $response = new Response();

        $event = new MvcEvent();
        $event->setError(Application::ERROR_ROUTER_NO_MATCH);
        $event->setRequest($request);
        $event->setResponse($response);

        return $event;
    }
}
