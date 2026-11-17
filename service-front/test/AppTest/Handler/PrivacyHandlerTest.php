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
    private string|false $oneLoginEnabled;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->oneLoginEnabled = getenv('ONELOGIN_ENABLED');
    }

    protected function tearDown(): void
    {
        putenv($this->oneLoginEnabled === false ? 'ONELOGIN_ENABLED' : 'ONELOGIN_ENABLED=' . $this->oneLoginEnabled);
    }

    public function testRendersPrivacyPageWithOneLoginOff(): void
    {
        putenv('ONELOGIN_ENABLED=false');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/privacy.twig', ['oneLoginEnabled' => false])
            ->willReturn('<html>privacy</html>');

        $response = (new PrivacyHandler($this->renderer))->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersPrivacyPageWithOneLoginOn(): void
    {
        putenv('ONELOGIN_ENABLED=true');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/privacy.twig', ['oneLoginEnabled' => true])
            ->willReturn('<html>privacy</html>');

        $response = (new PrivacyHandler($this->renderer))->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
