<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\SessionExpiryHandler;
use Application\Model\Service\Authentication\AuthenticationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionExpiryHandlerTest extends TestCase
{
    private AuthenticationService&MockObject $authenticationService;
    private SessionManagerSupport&MockObject $sessionManagerSupport;
    private SessionManager&MockObject $sessionManager;
    private SessionExpiryHandler $handler;

    protected function setUp(): void
    {
        $this->authenticationService = $this->createMock(AuthenticationService::class);
        $this->sessionManagerSupport = $this->createMock(SessionManagerSupport::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->sessionManagerSupport->method('getSessionManager')->willReturn($this->sessionManager);

        $this->handler = new SessionExpiryHandler(
            $this->authenticationService,
            $this->sessionManagerSupport,
        );
    }

    public function testReturnsRemainingSecondsWhenSessionActive(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->willReturn(300);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['remainingSeconds' => 300], $body);
    }

    public function testReturns204WhenSessionExpired(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->willReturn(0);

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $this->sessionManager
            ->expects($this->once())
            ->method('destroy')
            ->with(['clear_storage' => true]);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testReturns204WhenSessionExpiryReturnsNull(): void
    {
        $this->authenticationService
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->willReturn(null);

        $this->authenticationService
            ->expects($this->once())
            ->method('clearIdentity');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
