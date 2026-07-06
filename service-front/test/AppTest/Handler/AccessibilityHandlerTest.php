<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\AccessibilityHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccessibilityHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersAccessibilityPage(): void
    {
        $handler = new AccessibilityHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/accessibility.twig')
            ->willReturn('<html>accessibility</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
