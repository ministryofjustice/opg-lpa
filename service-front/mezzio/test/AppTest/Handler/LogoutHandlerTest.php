<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\LogoutHandler;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LogoutHandlerTest extends TestCase
{
    private SessionInterface&MockObject $session;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
    }

    private function createHandler(array $config = []): LogoutHandler
    {
        return new LogoutHandler($config);
    }

    private function createRequest(): ServerRequest
    {
        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);
    }

    public function testSessionIsClearedAndRegenerated(): void
    {
        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $handler = $this->createHandler(['redirects' => ['logout' => '/']]);
        $handler->handle($this->createRequest());
    }

    public function testRedirectsToConfiguredLogoutUrl(): void
    {
        $handler = $this->createHandler(['redirects' => ['logout' => '/goodbye']]);

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/goodbye', $response->getHeaderLine('Location'));
    }

    public function testRedirectsToRootWhenNoConfiguredUrl(): void
    {
        $handler = $this->createHandler([]);

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/', $response->getHeaderLine('Location'));
    }

    public function testHandlesNullSessionGracefully(): void
    {
        $handler = $this->createHandler(['redirects' => ['logout' => '/']]);

        $request = (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, null);

        $response = $handler->handle($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
