<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\SessionKeepAliveHandler;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\TestCase;

class SessionKeepAliveHandlerTest extends TestCase
{
    private SessionKeepAliveHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new SessionKeepAliveHandler();
    }

    public function testReturnsRefreshedTrueWhenSessionHasIdentity(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->with('identity')->willReturn(true);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['refreshed' => true], $body);
    }

    public function testReturnsRefreshedFalseWhenSessionHasNoIdentity(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('has')->with('identity')->willReturn(false);

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['refreshed' => false], $body);
    }

    public function testReturnsRefreshedFalseWhenNoSession(): void
    {
        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode((string)$response->getBody(), true);
        $this->assertEquals(['refreshed' => false], $body);
    }
}
