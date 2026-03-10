<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\TermsChangedHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TermsChangedHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private TermsChangedHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);

        $this->handler = new TermsChangedHandler(
            $this->renderer,
        );
    }

    public function testRendersTermsTemplate(): void
    {
        $request = (new ServerRequest())->withMethod('GET');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/authenticated/dashboard/terms.twig')
            ->willReturn('terms-html');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
