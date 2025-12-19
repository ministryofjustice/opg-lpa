<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\GuidanceHandler;
use Application\Model\Service\Guidance\Guidance;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class GuidanceHandlerTest extends MockeryTestCase
{
    public function testHandleRendersLayoutTemplateForNormalRequest(): void
    {
        $renderer = Mockery::mock(TemplateRendererInterface::class);
        $guidanceService = Mockery::mock(Guidance::class);

        $data = ['content' => 'guidance text'];

        $guidanceService
            ->shouldReceive('parseMarkdown')
            ->once()
            ->andReturn($data);

        $renderer
            ->shouldReceive('render')
            ->once()
            ->with(
                'guidance/opg-help-with-layout.twig',
                $data
            )
            ->andReturn('<html>with layout</html>');

        $handler = new GuidanceHandler(
            $renderer,
            $guidanceService
        );

        $request = new ServerRequest(); // no AJAX header
        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame('<html>with layout</html>', (string) $response->getBody());
    }

    public function testHandleRendersContentTemplateForAjaxRequest(): void
    {
        $renderer = Mockery::mock(TemplateRendererInterface::class);
        $guidanceService = Mockery::mock(Guidance::class);

        $data = ['content' => 'guidance text'];

        $guidanceService
            ->shouldReceive('parseMarkdown')
            ->once()
            ->andReturn($data);

        $renderer
            ->shouldReceive('render')
            ->once()
            ->with(
                'guidance/opg-help-content.twig',
                $data
            )
            ->andReturn('<html>content only</html>');

        $handler = new GuidanceHandler(
            $renderer,
            $guidanceService
        );

        $request = (new ServerRequest())
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame('<html>content only</html>', (string) $response->getBody());
    }
}
