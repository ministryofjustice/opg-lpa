<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SessionExpiryHandler;
use App\Storage\MezzioSessionStorage;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Model\Service\Authentication\Identity\User;
use DateTime;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionExpiryHandlerTest extends TestCase
{
    private LpaAuthAdapter&MockObject $authAdapter;
    private MezzioSessionStorage&MockObject $sessionStorage;
    private SessionInterface&MockObject $session;
    private SessionExpiryHandler $handler;

    protected function setUp(): void
    {
        $this->authAdapter = $this->createMock(LpaAuthAdapter::class);
        $this->sessionStorage = $this->createMock(MezzioSessionStorage::class);
        $this->session = $this->createMock(SessionInterface::class);

        $this->handler = new SessionExpiryHandler(
            $this->authAdapter,
            $this->sessionStorage,
        );
    }

    private function createRequest(): ServerRequest
    {
        return (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testReturnsRemainingSecondsWhenSessionActive(): void
    {
        $identity = new User('user-123', 'test-token', 3600, new DateTime());
        $this->sessionStorage->method('read')->willReturn($identity);

        $this->authAdapter
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->with('test-token')
            ->willReturn(['valid' => true, 'remainingSeconds' => 300]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['remainingSeconds' => 300], $body);
    }

    public function testReturns204WhenSessionExpired(): void
    {
        $identity = new User('user-123', 'test-token', 3600, new DateTime());
        $this->sessionStorage->method('read')->willReturn($identity);

        $this->authAdapter
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->willReturn(['valid' => false]);

        $this->sessionStorage->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testReturns204WhenSessionExpiryReturnsNull(): void
    {
        $identity = new User('user-123', 'test-token', 3600, new DateTime());
        $this->sessionStorage->method('read')->willReturn($identity);

        $this->authAdapter
            ->expects($this->once())
            ->method('getSessionExpiry')
            ->willReturn(null);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testReturns204WhenNoIdentityInSession(): void
    {
        $this->sessionStorage->method('read')->willReturn(null);
        $this->authAdapter->expects($this->never())->method('getSessionExpiry');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(EmptyResponse::class, $response);
        $this->assertEquals(204, $response->getStatusCode());
    }
}
