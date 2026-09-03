<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\AuthenticationMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Middleware\RequestAttribute;
use App\Authentication\AuthenticationService;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class AuthenticationMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private UrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private AuthenticationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->middleware = new AuthenticationMiddleware(
            $this->authenticationService,
            $this->urlHelper,
            $this->logger,
        );
    }

    private function stubMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };
    }

    private function makeRouteResult(string $routeName, array $options = []): RouteResult
    {
        $route = new Route('/' . $routeName, $this->stubMiddleware(), null, $routeName);
        $route->setOptions($options);
        return RouteResult::fromRoute($route, []);
    }

    public function testProcessWhenNoRoute(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $result);
    }

    public function testProcessWhenRouteDoesNotRequireAuth(): void
    {
        $routeResult = $this->makeRouteResult('application.login', ['unauthenticated_route' => true]);
        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->willReturn($expectedResponse);

        $result = $this->middleware->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testProcessWhenUserIsAuthenticated(): void
    {
        $identity = new User('user-1', 'token', 10000, new DateTime('2001-01-01'));

        $this->authenticationService->expects($this->once())->method('getIdentity')->willReturn($identity);

        $routeResult = $this->makeRouteResult('user/dashboard', ['requires_auth' => true]);
        $request = new ServerRequest()->withAttribute(RouteResult::class, $routeResult);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->willReturnCallback(function ($req) use ($expectedResponse, $identity): PSR7Response {
                $this->assertSame($identity, $req->getAttribute(RequestAttribute::IDENTITY));
                $this->assertIsInt($req->getAttribute('secondsUntilSessionExpires'));
                return $expectedResponse;
            });

        $result = $this->middleware->process($request, $handler);

        $this->assertEquals($expectedResponse, $result);
    }

    public static function unauthenticatedDataProvider(): array
    {
        return [
            'timeout - lpa route stores pre-auth url' => [
                'routeName' => 'lpa/view-docs',
                'requestPath' => '/lpa/12345678/view-docs',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => true,
            ],
            'internal system error' => [
                'routeName' => 'lpa/view-docs',
                'requestPath' => '/lpa/12345678/view-docs',
                'authFailureCode' => 503,
                'expectedState' => 'internal-system-error',
                'shouldSetPreAuthUrl' => true,
            ],
            'user delete route does not store url' => [
                'routeName' => 'user/delete',
                'requestPath' => '/user/delete',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
            'user dashboard route does not store url' => [
                'routeName' => 'user/dashboard/settings',
                'requestPath' => '/user/dashboard/settings',
                'authFailureCode' => null,
                'expectedState' => 'timeout',
                'shouldSetPreAuthUrl' => false,
            ],
        ];
    }

    #[DataProvider('unauthenticatedDataProvider')]
    public function testProcessWhenUnauthenticated(
        string $routeName,
        string $requestPath,
        ?int $authFailureCode,
        string $expectedState,
        bool $shouldSetPreAuthUrl
    ): void {
        $this->authenticationService->expects($this->once())->method('getIdentity')->willReturn(null);

        $session = $this->createMock(SessionInterface::class);

        if ($shouldSetPreAuthUrl) {
            $session->expects($this->once())
                ->method('set')
                ->with('pre_auth_request_url', $requestPath);
        } else {
            $session->expects($this->never())->method('set');
        }

        $session->method('get')
            ->with(IdentityTokenRefreshMiddleware::SESSION_KEY_AUTH_FAILURE_CODE)
            ->willReturn($authFailureCode);

        $routeResult = $this->makeRouteResult($routeName);
        $request = new ServerRequest(uri: $requestPath)
            ->withAttribute(RouteResult::class, $routeResult)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $expectedUrl = '/login/' . $expectedState;
        $this->urlHelper->expects($this->once())
            ->method('generate')
            ->with('application.login', ['state' => $expectedState])
            ->willReturn($expectedUrl);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $result = $this->middleware->process($request, $handler);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals($expectedUrl, $result->getHeaderLine('Location'));
    }

    #[DataProvider('hostilePathDataProvider')]
    public function testStoredPreAuthUrlIsAlwaysSameSite(string $requestPath, ?string $expected): void
    {
        $this->authenticationService->expects($this->once())->method('getIdentity')->willReturn(null);

        $stored  = [];
        $cleared = [];

        $session = $this->createMock(SessionInterface::class);
        $session->method('set')->willReturnCallback(
            function (string $key, mixed $value) use (&$stored): void {
                $stored[$key] = $value;
            }
        );
        $session->method('unset')->willReturnCallback(
            function (string $key) use (&$cleared): void {
                $cleared[] = $key;
            }
        );
        $session->method('get')->willReturn(null);

        $request = new ServerRequest(uri: $requestPath)
            ->withAttribute(RouteResult::class, RouteResult::fromRouteFailure(Route::HTTP_METHOD_ANY))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $this->urlHelper->method('generate')->willReturn('/login/timeout');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->middleware->process($request, $handler);

        $key = AuthenticationMiddleware::SESSION_KEY_PRE_AUTH_URL;

        $this->assertSame($expected, $stored[$key] ?? null);

        if ($expected === null) {
            $this->assertContains($key, $cleared, 'A rejected path must clear any earlier destination');
        }
    }

    /**
     * @return array<string, array{0: string, 1: ?string}>
     */
    public static function hostilePathDataProvider(): array
    {
        return [
            'protocol relative'   => ['//evil.example/', '/'],
            'backslash authority' => ['/\\evil.example', '/%5Cevil.example'],
            'absolute url'        => ['https://evil.example/x', '/x'],
            'crlf injection'      => ["/user/dashboard\r\nX-Injected: 1", '/user/dashboard__X-Injected:%201'],
            'fragment'            => ['/user/dashboard#x', '/user/dashboard'],
        ];
    }

    public function testLegitimateDeepLinkIsStored(): void
    {
        $this->authenticationService->expects($this->once())->method('getIdentity')->willReturn(null);

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('set')
            ->with(AuthenticationMiddleware::SESSION_KEY_PRE_AUTH_URL, '/lpa/12345678/checkout');
        $session->method('get')->willReturn(null);

        $this->logger->expects($this->never())->method('warning');

        $request = new ServerRequest(uri: '/lpa/12345678/checkout')
            ->withAttribute(RouteResult::class, $this->makeRouteResult('lpa/checkout'))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $this->urlHelper->method('generate')->willReturn('/login/timeout');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $this->middleware->process($request, $handler);
    }
}
