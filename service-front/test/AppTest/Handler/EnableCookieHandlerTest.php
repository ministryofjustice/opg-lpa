<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\EnableCookieHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EnableCookieHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersEnableCookiePage(): void
    {
        $handler = new EnableCookieHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/enable-cookie.twig')
            ->willReturn('<html>enable cookies</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
