<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\AccessibilityHandler;
use Application\Handler\ContactHandler;
use Application\Handler\EnableCookieHandler;
use Application\Handler\PrivacyHandler;
use Application\Handler\TermsHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StaticPageHandlersTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testEnableCookieHandlerRendersCorrectTemplate(): void
    {
        $handler = new EnableCookieHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/enable-cookie.twig')
            ->willReturn('<html>enable cookie</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testTermsHandlerRendersCorrectTemplate(): void
    {
        $handler = new TermsHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/terms.twig')
            ->willReturn('<html>terms</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAccessibilityHandlerRendersCorrectTemplate(): void
    {
        $handler = new AccessibilityHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/accessibility.twig')
            ->willReturn('<html>accessibility</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testPrivacyHandlerRendersCorrectTemplate(): void
    {
        $handler = new PrivacyHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/privacy.twig')
            ->willReturn('<html>privacy</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testContactHandlerRendersCorrectTemplate(): void
    {
        $handler = new ContactHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/contact.twig')
            ->willReturn('<html>contact</html>');

        $request = new ServerRequest();
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
