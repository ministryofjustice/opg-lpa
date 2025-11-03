<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\PingHandlerPingdom;
use Application\Model\Service\Date\DateService;
use Application\Model\Service\System\Status;
use DateTime;
use Laminas\Diactoros\Response\XmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Response;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use RuntimeException;

final class PingHandlerPingdomTest extends MockeryTestCase
{
    private Status|MockInterface $status;
    private DateService|MockInterface $dateService;

    public function setup(): void
    {
        $this->status = Mockery::mock(Status::class);
        $this->dateService = Mockery::mock(DateService::class);
    }

    public function testHandle(): void
    {
        $handler = new PingHandlerPingdom($this->status, $this->dateService);

        $this->status->shouldReceive('check')
            ->andReturn(['status' => Constants::STATUS_PASS])
            ->once();

        $this->dateService->shouldReceive('getNow')
            ->andReturn(new DateTime('2025-01-01 01:00:00'))
            ->once();

        $this->dateService->shouldReceive('getNow')
            ->andReturn(new DateTime('2025-01-01 01:00:01'))
            ->once();

        $result = $handler->handle(new ServerRequest());
        $bodyContents = $result->getBody()->getContents();

        $this->assertInstanceOf(XmlResponse::class, $result);
        $this->assertEquals(200, $result->getStatusCode());
        $this->assertStringContainsString('<pingdom_http_custom_check><status>OK</status>', $bodyContents);
        $this->assertStringContainsString('<response_time>1</response_time>', $bodyContents);
    }

    public function testHandleInvalidXML(): void
    {
        $handler = new PingHandlerPingdom($this->status, new DateService(), '<invalid><xml');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('could not marshal XML');

        $handler->handle(new ServerRequest());
    }

    public function testHandleError(): void
    {
        $handler = new PingHandlerPingdom($this->status, new DateService(),);

        $this->status->shouldReceive('check')
            ->andReturn(['status' => Constants::STATUS_FAIL])
            ->once();

        $result = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(XmlResponse::class, $result);
        $this->assertEquals(500, $result->getStatusCode());
        $this->assertStringContainsString(
            '<pingdom_http_custom_check><status>ERROR</status>',
            $result->getBody()->getContents()
        );
    }
}
