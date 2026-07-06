<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\PingHandlerJson;
use App\Service\System\StatusService;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PingHandlerJsonTest extends TestCase
{
    private StatusService&MockObject $statusService;

    protected function setUp(): void
    {
        $this->statusService = $this->createMock(StatusService::class);
    }

    public function testReturnsJsonWithConfiguredTag(): void
    {
        $this->statusService->expects($this->once())
            ->method('check')
            ->willReturn(['status' => 'pass', 'ok' => true]);

        $response = (new PingHandlerJson(
            ['version' => ['tag' => 'build-123']],
            $this->statusService,
        ))->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(
            ['status' => 'pass', 'ok' => true, 'tag' => 'build-123'],
            json_decode((string) $response->getBody(), true),
        );
    }

    public function testReturnsEmptyTagWhenConfigTagMissing(): void
    {
        $this->statusService->expects($this->once())
            ->method('check')
            ->willReturn(['status' => 'pass', 'ok' => true]);

        $response = (new PingHandlerJson([], $this->statusService))->handle(new ServerRequest());

        $this->assertSame(
            ['status' => 'pass', 'ok' => true, 'tag' => ''],
            json_decode((string) $response->getBody(), true),
        );
    }
}
