<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\SessionKeepAliveHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Session\SessionManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SessionKeepAliveHandlerTest extends TestCase
{
    private SessionManager&MockObject $sessionManager;
    private SessionKeepAliveHandler $handler;

    protected function setUp(): void
    {
        $this->sessionManager = $this->createMock(SessionManager::class);

        $this->handler = new SessionKeepAliveHandler(
            $this->sessionManager,
        );
    }

    public function testReturnsRefreshedTrueWhenSessionExists(): void
    {
        $this->sessionManager
            ->expects($this->once())
            ->method('sessionExists')
            ->willReturn(true);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode((string) $response->getBody(), true);
        $this->assertTrue($body['refreshed']);
    }

    public function testReturnsRefreshedFalseWhenSessionDoesNotExist(): void
    {
        $this->sessionManager
            ->expects($this->once())
            ->method('sessionExists')
            ->willReturn(false);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);

        $body = json_decode((string) $response->getBody(), true);
        $this->assertFalse($body['refreshed']);
    }
}
