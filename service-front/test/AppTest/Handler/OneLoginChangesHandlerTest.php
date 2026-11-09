<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\OneLoginChangesHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;

class OneLoginChangesHandlerTest extends TestCase
{
    public function testRendersTheSignInChangesPage(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/home/onelogin-changes.twig')
            ->willReturn('<html>sign-in changes</html>');

        $response = (new OneLoginChangesHandler($renderer))->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('<html>sign-in changes</html>', (string) $response->getBody());
    }
}
