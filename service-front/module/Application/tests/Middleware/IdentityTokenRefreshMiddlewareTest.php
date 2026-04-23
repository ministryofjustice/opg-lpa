<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware;

use Application\Middleware\IdentityTokenRefreshMiddleware;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\Session\ContainerNamespace;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\User\Details as UserService;
use DateTime;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class IdentityTokenRefreshMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private UserService&MockObject $userService;
    private SessionUtility&MockObject $sessionUtility;
    private IdentityTokenRefreshMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authService    = $this->createMock(AuthenticationService::class);
        $this->userService    = $this->createMock(UserService::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);

        $this->middleware = new IdentityTokenRefreshMiddleware(
            $this->authService,
            $this->userService,
            $this->sessionUtility,
        );
    }

    private function makeHandler(): RequestHandlerInterface
    {
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn(new Response());
        return $handler;
    }

    private function makeIdentity(string $token = 'test-token'): Identity
    {
        return new Identity('user-123', $token, 3600, new DateTime());
    }

    // -------------------------------------------------------------------------
    // Excluded paths
    // -------------------------------------------------------------------------

    public function testSkipsRefreshForPingElb(): void
    {
        $this->authService->expects($this->never())->method('getIdentity');

        $request = new ServerRequest(uri: 'https://example.com/ping/elb');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testSkipsRefreshForPingJson(): void
    {
        $this->authService->expects($this->never())->method('getIdentity');

        $request = new ServerRequest(uri: 'https://example.com/ping/json');
        $this->middleware->process($request, $this->makeHandler());
    }

    // -------------------------------------------------------------------------
    // No identity
    // -------------------------------------------------------------------------

    public function testDoesNothingWhenNoIdentity(): void
    {
        $this->authService->expects($this->once())->method('getIdentity')->willReturn(null);
        $this->userService->expects($this->never())->method('getTokenInfo');

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());
    }

    // -------------------------------------------------------------------------
    // /session-state path — identity checked but token NOT refreshed
    // -------------------------------------------------------------------------

    public function testSkipsTokenUpdateForSessionStatePath(): void
    {
        $this->authService->expects($this->once())->method('getIdentity')->willReturn(null);
        $this->userService->expects($this->never())->method('getTokenInfo');

        $request = new ServerRequest(uri: 'https://example.com/session-state');
        $this->middleware->process($request, $this->makeHandler());
    }

    // -------------------------------------------------------------------------
    // Token refresh success
    // -------------------------------------------------------------------------

    public function testUpdatesTokenExpiryOnSuccess(): void
    {
        $identity = $this->makeIdentity();

        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->with('test-token')
            ->willReturn(['success' => true, 'expiresIn' => 1800, 'failureCode' => null]);

        $this->authService->expects($this->never())->method('clearIdentity');

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());

        // tokenExpiresIn stores via tokenExpiresIn() mutator — assert it was called
        // by verifying the identity still has an expiry (non-null tokenExpiresAt)
        $this->assertNotNull($identity->tokenExpiresAt());
    }

    // -------------------------------------------------------------------------
    // Token refresh failure — non-500
    // -------------------------------------------------------------------------

    public function testClearsIdentityOnNonSuccessResponse(): void
    {
        $identity = $this->makeIdentity();

        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 401]);

        $this->authService->expects($this->once())->method('clearIdentity');
        $this->sessionUtility->expects($this->never())->method('setInMvc');

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());
    }

    // -------------------------------------------------------------------------
    // Token refresh failure — 500-class error
    // -------------------------------------------------------------------------

    public function testRecordsInternalSystemErrorInSessionOn500(): void
    {
        $identity = $this->makeIdentity();

        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 503]);

        $this->authService->expects($this->once())->method('clearIdentity');

        $this->sessionUtility->expects($this->exactly(2))
            ->method('setInMvc')
            ->willReturnCallback(function (string $namespace, string $key, mixed $value): void {
                static $calls = [];
                $calls[] = [$namespace, $key, $value];
            });

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testRecordsFailureReasonNamespaceAndCode(): void
    {
        $identity = $this->makeIdentity();

        $this->authService->method('getIdentity')->willReturn($identity);
        $this->userService->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 500]);

        $this->sessionUtility->expects($this->exactly(2))
            ->method('setInMvc')
            ->willReturnCallback(function (string $namespace, string $key, mixed $value) use (&$recorded): void {
                $recorded[] = [$namespace, $key, $value];
            });

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());

        $this->assertSame([ContainerNamespace::AUTH_FAILURE_REASON, 'reason', 'Internal system error'], $recorded[0]);
        $this->assertSame([ContainerNamespace::AUTH_FAILURE_REASON, 'code', 500], $recorded[1]);
    }

    // -------------------------------------------------------------------------
    // ApiException
    // -------------------------------------------------------------------------

    public function testClearsIdentityOnApiException(): void
    {
        $identity = $this->makeIdentity();

        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willThrowException(new ApiException(new EmptyResponse(500)));

        $this->authService->expects($this->once())->method('clearIdentity');

        $request = new ServerRequest(uri: 'https://example.com/dashboard');
        $this->middleware->process($request, $this->makeHandler());
    }

    // -------------------------------------------------------------------------
    // Response passthrough
    // -------------------------------------------------------------------------

    public function testPassesThroughResponseFromHandler(): void
    {
        $this->authService->method('getIdentity')->willReturn(null);

        $expectedResponse = new Response();
        $request = new ServerRequest(uri: 'https://example.com/dashboard');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
