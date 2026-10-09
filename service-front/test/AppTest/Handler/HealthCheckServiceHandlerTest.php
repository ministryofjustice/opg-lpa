<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HealthCheckServiceHandler;
use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HealthCheckServiceHandlerTest extends TestCase
{
    private StatusService&MockObject $statusService;

    protected function setUp(): void
    {
        $this->statusService = $this->createMock(StatusService::class);
    }

    public function testRendersPingTemplateWithStatus(): void
    {
        $this->statusService->expects($this->once())
            ->method('checkApi')
            ->willReturn(['ok' => true]);
        $this->statusService->expects($this->once())
            ->method('checkSession')
            ->willReturn(['ok' => true]);
        $this->statusService->expects($this->once())
            ->method('checkDynamo')
            ->willReturn(['ok' => true]);

        $response = (new HealthCheckServiceHandler($this->statusService))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"api":{"ok":true},"sessionSaveHandler":{"ok":true},"dynamo":{"ok":true},"ok":true}', (string) $response->getBody());
    }
}
