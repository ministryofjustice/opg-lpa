<?php

declare(strict_types=1);

namespace AppTest\Middleware;

use App\Authentication\AuthenticationService;
use App\Middleware\RequestAttribute;
use App\Middleware\SessionIdentityMiddleware;
use App\Model\Service\Authentication\Identity\User;
use App\Storage\MezzioSessionStorage;
use Laminas\Diactoros\Response as PSR7Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;

class SessionIdentityMiddlewareTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;

    protected function setUp(): void
    {
        $this->authenticationService = $this->getMockBuilder(AuthenticationService::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getStorage'])
            ->getMock();
    }

    public function testProcessWritesIdentityToStorageAndAddsRequestAttribute(): void
    {
        $sessionData = [
            'userId' => 'user-123',
            'token' => 'token-abc',
            'tokenExpiresAt' => date('c', time() + 3600),
            'lastLogin' => '2024-01-01T12:34:56+00:00',
        ];

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('has')
            ->with('identity')
            ->willReturn(true);
        $session->expects($this->once())
            ->method('get')
            ->with('identity')
            ->willReturn($sessionData);

        $storage = $this->createMock(MezzioSessionStorage::class);
        $storage->expects($this->once())
            ->method('write')
            ->with($this->callback(function ($identity): bool {
                if (!$identity instanceof User) {
                    return false;
                }

                $expiresIn = $identity->tokenExpiresAt()->getTimestamp() - time();

                return $identity->id() === 'user-123'
                    && $identity->token() === 'token-abc'
                    && $identity->lastLogin()->format('c') === '2024-01-01T12:34:56+00:00'
                    && $expiresIn > 3500
                    && $expiresIn <= 3600;
            }));

        $this->authenticationService->expects($this->once())
            ->method('getStorage')
            ->willReturn($storage);

        $request = (new ServerRequest())->withAttribute(SessionInterface::class, $session);
        $expectedResponse = new PSR7Response();

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(function ($request): bool {
                $identity = $request->getAttribute(RequestAttribute::IDENTITY);

                return $identity instanceof User
                    && $identity->id() === 'user-123'
                    && $identity->token() === 'token-abc';
            }))
            ->willReturn($expectedResponse);

        $response = (new SessionIdentityMiddleware($this->authenticationService))->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessPassesThroughWhenNoSession(): void
    {
        $request = new ServerRequest();
        $expectedResponse = new PSR7Response();

        $this->authenticationService->expects($this->never())
            ->method('getStorage');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new SessionIdentityMiddleware($this->authenticationService))->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }

    public function testProcessPassesThroughWhenSessionDataIsMalformed(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('has')
            ->with('identity')
            ->willReturn(true);
        $session->expects($this->once())
            ->method('get')
            ->with('identity')
            ->willReturn(['userId' => 'user-123']);

        $request = (new ServerRequest())->withAttribute(SessionInterface::class, $session);
        $expectedResponse = new PSR7Response();

        $this->authenticationService->expects($this->never())
            ->method('getStorage');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = (new SessionIdentityMiddleware($this->authenticationService))->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
