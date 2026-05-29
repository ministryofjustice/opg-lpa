<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\AuthenticationMiddleware;
use App\Middleware\IdentityTokenRefreshMiddleware;
use Application\Middleware\RequestAttribute;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User;
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

class AuthenticationMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private UrlHelper&MockObject $urlHelper;
    private AuthenticationMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);

        $this->middleware = new AuthenticationMiddleware(
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
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
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
        $request = (new ServerRequest())->withAttribute(RouteResult::class, $routeResult);
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
                'expectedState' => 'internalSystemError',
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

    /**
     * @dataProvider unauthenticatedDataProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('unauthenticatedDataProvider')]
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
        $request = (new ServerRequest(uri: $requestPath))
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
}
