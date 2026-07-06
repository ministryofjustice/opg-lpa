<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\GuidanceHandler;
use App\Service\Guidance\GuidanceService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GuidanceHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private GuidanceService&MockObject $guidanceService;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->guidanceService = $this->createMock(GuidanceService::class);
    }

    public function testRendersGuidancePageWithLayoutForStandardRequest(): void
    {
        $handler = new GuidanceHandler($this->renderer, $this->guidanceService);
        $data = ['sections' => [['id' => 'topic-1']]];

        $this->guidanceService
            ->expects($this->once())
            ->method('parseMarkdown')
            ->willReturn($data);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('guidance/opg-help-with-layout.twig', $data)
            ->willReturn('<html>guidance</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersGuidanceContentTemplateForAjaxRequest(): void
    {
        $handler = new GuidanceHandler($this->renderer, $this->guidanceService);
        $data = ['sections' => [['id' => 'topic-1']]];

        $this->guidanceService
            ->expects($this->once())
            ->method('parseMarkdown')
            ->willReturn($data);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('guidance/opg-help-content.twig', $data)
            ->willReturn('<html>guidance content</html>');

        $request = (new ServerRequest())
            ->withHeader('X-Requested-With', 'XMLHttpRequest');

        $response = $handler->handle($request);

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
