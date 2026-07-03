<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\StatsHandler;
use App\Service\Stats\StatsService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StatsHandlerTest extends TestCase
{
    private StatsService&MockObject $statsService;
    private TemplateRendererInterface&MockObject $renderer;

    protected function setUp(): void
    {
        $this->statsService = $this->createMock(StatsService::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testRendersStatsTemplateWithArrayData(): void
    {
        $handler = new StatsHandler($this->statsService, $this->renderer);
        $stats = ['total' => 10, 'active' => 3];

        $this->statsService
            ->expects($this->once())
            ->method('getApiStats')
            ->willReturn($stats);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/stats', $stats)
            ->willReturn('<html>stats</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersStatsTemplateWithEmptyDataWhenServiceDoesNotReturnArray(): void
    {
        $handler = new StatsHandler($this->statsService, $this->renderer);

        $this->statsService
            ->expects($this->once())
            ->method('getApiStats')
            ->willReturn('unavailable');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with('application/general/stats', [])
            ->willReturn('<html>stats</html>');

        $response = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
