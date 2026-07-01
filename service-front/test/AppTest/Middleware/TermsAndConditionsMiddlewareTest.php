<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Authentication\AuthenticationService;
use App\Middleware\TermsAndConditionsMiddleware;
use App\Model\Service\Authentication\Identity\User;
use DateTime;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Helper\UrlHelper;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TermsAndConditionsMiddlewareTest extends TestCase
{
    private array $config;
    private AuthenticationService&MockObject $authenticationService;
    private UrlHelper&MockObject $urlHelper;
    private TermsAndConditionsMiddleware $middleware;

    protected function setUp(): void
    {
        $this->config = ['terms' => ['lastUpdated' => '2000-01-01']];
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->middleware = new TermsAndConditionsMiddleware(
            $this->config,
            $this->authenticationService,
            $this->urlHelper,
        );
    }

    private function stubMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                \Psr\Http\Message\ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): \Psr\Http\Message\ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    private function makeRouteResult(string $routeName): RouteResult
    {
        $route = new Route('/' . $routeName, $this->stubMiddleware(), null, $routeName);
        return RouteResult::fromRoute($route, []);
    }

    private function makeHandler(PSR7Response $response): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($response);
        return $handler;
    }

    /**
     * When the current route is the terms-changed page itself, the middleware
     * must pass through unconditionally to avoid a redirect loop.
     */
    public function testPassesThroughOnTermsChangedRoute(): void
    {
        $routeResult = $this->makeRouteResult('user/dashboard/terms-changed');
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $this->authenticationService->expects($this->never())->method('getIdentity');

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * When no identity exists (unauthenticated), the middleware passes through.
     */
    public function testPassesThroughWhenNoIdentity(): void
    {
        $this->authenticationService->method('getIdentity')->willReturn(null);

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * When the user last logged in AFTER the terms update date, no redirect occurs.
     */
    public function testPassesThroughWhenUserLoggedInAfterTermsUpdate(): void
    {
        $identity = new User('1', 'token', 10000, new DateTime('2001-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * When lastLogin is before terms update but the session flag is already set
     * (user was already shown the terms-changed page this session), pass through.
     */
    public function testPassesThroughWhenTermsAlreadySeenInSession(): void
    {
        $identity = new User('1', 'token', 10000, new DateTime('1999-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->with('termsAndConditionsCheckSeen')->willReturn(true);
        $session->expects($this->never())->method('set');

        $request = (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * When lastLogin is before terms update and session flag is NOT set,
     * the middleware sets the flag and redirects to the terms-changed page.
     */
    public function testRedirectsAndSetsSessionFlagWhenTermsNotSeen(): void
    {
        $identity = new User('1', 'token', 10000, new DateTime('1999-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->with('termsAndConditionsCheckSeen')->willReturn(false);
        $session->expects($this->once())->method('set')->with('termsAndConditionsCheckSeen', true);

        $this->urlHelper->method('generate')
            ->with('user/dashboard/terms-changed')
            ->willReturn('/user/dashboard/new-terms');

        $request = (new ServerRequest())->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/user/dashboard/new-terms', $result->getHeaderLine('Location'));
    }

    /**
     * When the session attribute is null (no session), the middleware still redirects
     * (cannot set flag, but should not crash).
     */
    public function testRedirectsWhenSessionIsNull(): void
    {
        $identity = new User('1', 'token', 10000, new DateTime('1999-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $this->urlHelper->method('generate')
            ->with('user/dashboard/terms-changed')
            ->willReturn('/user/dashboard/new-terms');

        // No session attribute set on request
        $request = new ServerRequest();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('/user/dashboard/new-terms', $result->getHeaderLine('Location'));
    }

    /**
     * When config has no 'terms.lastUpdated' or it is an invalid date string,
     * the middleware passes through (fail-open: don't block users on config error).
     */
    public function testPassesThroughWhenTermsConfigIsInvalid(): void
    {
        $middleware = new TermsAndConditionsMiddleware(
            ['terms' => ['lastUpdated' => 'not-a-date']],
            $this->authenticationService,
            $this->urlHelper,
        );

        $identity = new User('1', 'token', 10000, new DateTime('1999-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    /**
     * When config has no 'terms' key at all, the middleware passes through.
     */
    public function testPassesThroughWhenTermsConfigIsMissing(): void
    {
        $middleware = new TermsAndConditionsMiddleware(
            [],
            $this->authenticationService,
            $this->urlHelper,
        );

        $identity = new User('1', 'token', 10000, new DateTime('1999-01-01'));
        $this->authenticationService->method('getIdentity')->willReturn($identity);

        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }
}
