<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\FeedbackThanksHandler;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;

class FeedbackThanksHandlerTest extends TestCase
{
    public function testHandleWithReturnTargetInQuery(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/thanks.twig',
                $this->callback(fn ($ctx) =>
                    isset($ctx['returnTarget'])
                    && $ctx['returnTarget'] === '/somewhere')
            )
            ->willReturn('<html>thanks with target</html>');

        $handler = new FeedbackThanksHandler($renderer);

        $request = (new ServerRequest())
            ->withQueryParams(['returnTarget' => urlencode('/somewhere')]);

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertStringContainsString('thanks with target', (string) $response->getBody());
    }

    public function testHandleWithoutReturnTargetDefaultsToRoot(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/thanks.twig',
                $this->callback(fn ($ctx) =>
                    isset($ctx['returnTarget'])
                    && $ctx['returnTarget'] === '/')
            )
            ->willReturn('<html>thanks default</html>');

        $handler = new FeedbackThanksHandler($renderer);

        $request = new ServerRequest();

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertStringContainsString('thanks default', (string) $response->getBody());
    }
}
