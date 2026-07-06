<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\PingHandlerPingdom;
use App\Service\Date\DateService;
use App\Service\System\StatusService;
use DateTime;
use Laminas\Diactoros\Response\XmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\Constants;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PingHandlerPingdomTest extends TestCase
{
    private StatusService&MockObject $statusService;
    private DateService&MockObject $dateService;

    protected function setUp(): void
    {
        $this->statusService = $this->createMock(StatusService::class);
        $this->dateService = $this->createMock(DateService::class);
    }

    public function testPassStatusRendersOkResponse(): void
    {
        $this->dateService->expects($this->exactly(2))
            ->method('getNow')
            ->willReturnOnConsecutiveCalls(
                new DateTime('@1704067200'),
                new DateTime('@1704067202'),
            );

        $this->statusService->expects($this->once())
            ->method('check')
            ->willReturn(['status' => Constants::STATUS_PASS]);

        $response = (new PingHandlerPingdom($this->statusService, $this->dateService))->handle(new ServerRequest());

        $this->assertInstanceOf(XmlResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        $xml = simplexml_load_string((string) $response->getBody());

        $this->assertNotFalse($xml);
        $this->assertSame('OK', (string) $xml->status);
        $this->assertSame('2', (string) $xml->response_time);
    }

    public function testFailStatusRendersErrorResponse(): void
    {
        $this->dateService->expects($this->exactly(2))
            ->method('getNow')
            ->willReturnOnConsecutiveCalls(
                new DateTime('@1704067200'),
                new DateTime('@1704067201'),
            );

        $this->statusService->expects($this->once())
            ->method('check')
            ->willReturn(['status' => Constants::STATUS_FAIL]);

        $response = (new PingHandlerPingdom($this->statusService, $this->dateService))->handle(new ServerRequest());

        $this->assertSame(500, $response->getStatusCode());

        $xml = simplexml_load_string((string) $response->getBody());

        $this->assertNotFalse($xml);
        $this->assertSame('ERROR', (string) $xml->status);
        $this->assertSame('1', (string) $xml->response_time);
    }

    public function testInvalidXmlTemplateThrowsRuntimeException(): void
    {
        $this->dateService->expects($this->once())
            ->method('getNow')
            ->willReturn(new DateTime('@1704067200'));

        $this->statusService->expects($this->never())
            ->method('check');

        $handler = new PingHandlerPingdom($this->statusService, $this->dateService, '<pingdom_http_custom_check');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not marshal XML');

        $handler->handle(new ServerRequest());
    }
}
