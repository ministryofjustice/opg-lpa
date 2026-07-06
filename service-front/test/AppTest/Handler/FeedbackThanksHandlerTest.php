<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\FeedbackThanksHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FeedbackThanksHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersThanksPageWithDecodedReturnTarget(): void
    {
        $handler = new FeedbackThanksHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/thanks.twig',
                ['returnTarget' => '/guide?ref=email']
            )
            ->willReturn('<html>thanks</html>');

        $request = new ServerRequest(queryParams: ['returnTarget' => '%2Fguide%3Fref%3Demail']);
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDefaultsReturnTargetToHomeWhenMissing(): void
    {
        $handler = new FeedbackThanksHandler($this->renderer);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/thanks.twig',
                ['returnTarget' => '/']
            )
            ->willReturn('<html>thanks</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
