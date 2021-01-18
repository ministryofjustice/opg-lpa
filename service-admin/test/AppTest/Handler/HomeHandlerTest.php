<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HomeHandler;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;

class HomeHandlerTest extends TestCase
{
    public function testReturnsHtmlResponseWhenTemplateRendererProvided()
    {
        $rendererProphecy = $this->prophesize(TemplateRendererInterface::class);
        $rendererProphecy->render('app::home')
            ->willReturn('');

        //  Set up the handler
        $handler = new HomeHandler();
        $handler->setTemplateRenderer($rendererProphecy->reveal());

        $requestProphecy = $this->prophesize(ServerRequestInterface::class);

        $response = $handler->handle($requestProphecy->reveal());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
