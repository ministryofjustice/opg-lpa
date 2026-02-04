<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\AccessibilityHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AccessibilityHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private AccessibilityHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->handler = new AccessibilityHandler($this->renderer);
    }

    public function testRendersCorrectTemplate(): void
    {
        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/accessibility.twig')
            ->willReturn('<html>accessibility</html>');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testReturns200StatusCode(): void
    {
        $this->renderer
            ->method('render')
            ->willReturn('<html>accessibility</html>');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testReturnsHtmlContent(): void
    {
        $expectedHtml = '<html><body>Accessibility statement</body></html>';

        $this->renderer
            ->method('render')
            ->willReturn($expectedHtml);

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertEquals($expectedHtml, (string)$response->getBody());
    }

    public function testSetsCorrectContentType(): void
    {
        $this->renderer
            ->method('render')
            ->willReturn('<html>content</html>');

        $request = new ServerRequest();
        $response = $this->handler->handle($request);

        $this->assertStringContainsString('text/html', $response->getHeaderLine('Content-Type'));
    }
}
