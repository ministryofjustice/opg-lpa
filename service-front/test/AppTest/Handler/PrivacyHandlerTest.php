<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\PrivacyHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrivacyHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersPrivacyPage(): void
    {
        $handler = new PrivacyHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/privacy.twig')
            ->willReturn('<html>privacy</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
