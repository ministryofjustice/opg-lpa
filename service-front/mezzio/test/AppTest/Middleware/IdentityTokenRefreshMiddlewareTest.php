<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Middleware\IdentityTokenRefreshMiddleware;
use App\Storage\MezzioSessionStorage;
use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Authentication\Identity\User as Identity;
use Application\Model\Service\User\Details as UserService;
use DateTime;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class IdentityTokenRefreshMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private UserService&MockObject $userService;
    private MezzioSessionStorage&MockObject $storage;
    private ApiClient&MockObject $apiClient;
    private SessionInterface&MockObject $session;
    private IdentityTokenRefreshMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);
        $this->userService = $this->createMock(UserService::class);
        $this->storage = $this->createMock(MezzioSessionStorage::class);
        $this->apiClient = $this->createMock(ApiClient::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->middleware = new IdentityTokenRefreshMiddleware(
            $this->authService,
            $this->userService,
            $this->storage,
            $this->apiClient,
        );

        $logger = $this->createMock(LoggerInterface::class);
        $this->middleware->setLogger($logger);
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

    private function makeRequestWithSession(string $uri = 'https://example.com/dashboard'): ServerRequest
    {
        return (new ServerRequest(uri: $uri))
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testSkipsRefreshForPingElb(): void
    {
        $this->authService->expects($this->never())->method('getIdentity');
        $this->storage->expects($this->never())->method('setSession');

        $request = new ServerRequest(uri: 'https://example.com/ping/elb');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testSkipsRefreshForPingJson(): void
    {
        $this->authService->expects($this->never())->method('getIdentity');

        $request = new ServerRequest(uri: 'https://example.com/ping/json');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testSetsSessionOnStorageAndPropagatesToken(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->expects($this->once())->method('setSession')->with($this->session);
        $this->storage->expects($this->once())->method('read')->willReturn($identity);
        $this->apiClient->expects($this->once())->method('updateToken')->with('test-token');
        $this->authService->method('getIdentity')->willReturn(null);

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testDoesNothingWhenNoIdentityAfterRefresh(): void
    {
        $this->storage->method('read')->willReturn(null);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn(null);
        $this->userService->expects($this->never())->method('getTokenInfo');

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testSkipsTokenUpdateForSessionStatePath(): void
    {
        $this->storage->method('read')->willReturn(null);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn(null);
        $this->userService->expects($this->never())->method('getTokenInfo');

        $request = $this->makeRequestWithSession('https://example.com/session-state');
        $this->middleware->process($request, $this->makeHandler());
    }

    public function testUpdatesTokenExpiryOnSuccess(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->method('read')->willReturn($identity);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->with('test-token')
            ->willReturn(['success' => true, 'expiresIn' => 1800, 'failureCode' => null]);

        $this->authService->expects($this->never())->method('clearIdentity');
        $this->storage->expects($this->once())->method('write')->with($identity);

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());

        $this->assertNotNull($identity->tokenExpiresAt());
    }

    public function testClearsIdentityOnNonSuccessResponse(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->method('read')->willReturn($identity);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 401]);

        $this->authService->expects($this->once())->method('clearIdentity');
        $this->session->expects($this->never())->method('set');

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testRecordsFailureCodeInSessionOn500(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->method('read')->willReturn($identity);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 503]);

        $this->authService->expects($this->once())->method('clearIdentity');

        $this->session->expects($this->once())
            ->method('set')
            ->with(IdentityTokenRefreshMiddleware::SESSION_KEY_AUTH_FAILURE_CODE, 503);

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testDoesNotSetSessionKeyWhenFailureCodeBelow500(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->method('read')->willReturn($identity);
        $this->authService->method('getIdentity')->willReturn($identity);
        $this->userService->method('getTokenInfo')
            ->willReturn(['success' => false, 'expiresIn' => null, 'failureCode' => 401]);

        $this->session->expects($this->never())->method('set');

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testClearsIdentityOnApiException(): void
    {
        $identity = $this->makeIdentity();

        $this->storage->method('read')->willReturn($identity);
        $this->authService->expects($this->once())->method('getIdentity')->willReturn($identity);
        $this->userService->expects($this->once())
            ->method('getTokenInfo')
            ->willThrowException(new ApiException(new EmptyResponse(500)));

        $this->authService->expects($this->once())->method('clearIdentity');

        $this->middleware->process($this->makeRequestWithSession(), $this->makeHandler());
    }

    public function testPassesThroughResponseFromHandler(): void
    {
        $this->storage->method('read')->willReturn(null);
        $this->authService->method('getIdentity')->willReturn(null);

        $expectedResponse = new Response();
        $request = $this->makeRequestWithSession();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request)->willReturn($expectedResponse);

        $response = $this->middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
