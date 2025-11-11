<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\PingHandler;
use Application\Model\Service\System\Status;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class PingHandlerTest extends MockeryTestCase
{
    public function testHandle(): void
    {
        $status = Mockery::mock(Status::class);
        $status->shouldReceive('check')
            ->andReturn(['status' => Constants::STATUS_PASS])
            ->once();

        $handler = new PingHandler($status);

        $result = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('{"status":{"status":"pass"}}', $result->getBody()->getContents());
    }
}
