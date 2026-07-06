<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\PingHandler;
use App\Service\System\StatusService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PingHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private StatusService&MockObject $statusService;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->statusService = $this->createMock(StatusService::class);
    }

    public function testRendersPingTemplateWithStatus(): void
    {
        $status = ['status' => 'pass', 'ok' => true];

        $this->statusService->expects($this->once())
            ->method('check')
            ->willReturn($status);

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/general/ping/index.twig', ['status' => $status])
            ->willReturn('<h1>pong</h1>');

        $response = (new PingHandler($this->renderer, $this->statusService))->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame('<h1>pong</h1>', (string) $response->getBody());
    }
}
