<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\HealthCheckDependenciesHandler;
use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class HealthCheckDependenciesHandlerTest extends TestCase
{
    private StatusService&MockObject $statusService;

    protected function setUp(): void
    {
        $this->statusService = $this->createMock(StatusService::class);
    }

    public function testRendersPingTemplateWithStatus(): void
    {
        $this->statusService->expects($this->once())
            ->method('checkMail')
            ->willReturn(['ok' => true, 'mail' => 'good']);
        $this->statusService->expects($this->once())
            ->method('checkOrdnanceSurvey')
            ->willReturn(['ok' => true, 'os' => 'good']);
        $this->statusService->expects($this->once())
            ->method('checkPay')
            ->willReturn(['ok' => true, 'pay' => 'good']);
        $this->statusService->expects($this->once())
            ->method('checkApiDependencies')
            ->willReturn(['ok' => true, 'api' => 'good']);

        $response = (new HealthCheckDependenciesHandler($this->statusService))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame('{"mail":{"ok":true,"mail":"good"},"ordnanceSurvey":{"ok":true,"os":"good"},"pay":{"ok":true,"pay":"good"},"api":{"ok":true,"api":"good"},"ok":true}', (string) $response->getBody());
    }
}
