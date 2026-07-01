<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\DeletedAccountHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeletedAccountHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private DeletedAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->handler = new DeletedAccountHandler($this->renderer);
    }

    public function testSessionIsClearedAndRegeneratedThenRendersTemplate(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())->method('clear');
        $session->expects($this->once())->method('regenerate');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/deleted.twig')
            ->willReturn('<html>deleted</html>');

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testHandlesNullSessionGracefully(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/deleted.twig')
            ->willReturn('<html>deleted</html>');

        $request = (new ServerRequest())
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, null);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
