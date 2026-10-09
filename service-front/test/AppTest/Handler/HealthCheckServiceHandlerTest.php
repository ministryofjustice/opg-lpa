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
            ->willReturn(['api-good']);
        $this->statusService->expects($this->once())
            ->method('checkSession')
            ->willReturn(['session-good']);
        $this->statusService->expects($this->once())
            ->method('checkDynamo')
            ->willReturn(['dynamo-good']);

        $response = (new HealthCheckServiceHandler($this->statusService))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"api":["api-good"],"sessionSaveHandler":["session-good"],"dynamo":["dynamo-good"]}', (string) $response->getBody());
    }
}
