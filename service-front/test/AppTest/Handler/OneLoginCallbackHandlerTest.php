<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginCallbackHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OneLoginCallbackHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private OneLoginCallbackHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->handler  = new OneLoginCallbackHandler($this->renderer);
    }

    public function testHandleRendersStubTemplateWith200(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/auth/onelogin-callback.twig')
            ->willReturn('<html>stub</html>');

        $response = $this->handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }
}
