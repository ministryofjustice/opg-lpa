<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\PingHandlerJson;
use Application\Model\Service\System\Status;
use GuzzleHttp\Psr7\Request;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\Constants;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class PingHandlerJsonTest extends MockeryTestCase
{
    public function testJsonAction(): void
    {
        $config = ['version' => ['tag' => '1.0']];

        $status = Mockery::mock(Status::class);
        $status->shouldReceive('check')->andReturn([
            'status' => Constants::STATUS_PASS,
        ])->once();

        $handler = new PingHandlerJson($config, $status);

        /** @var JsonModel $result */
        $result = $handler->handle(new ServerRequest());

        $this->assertInstanceOf(JsonResponse::class, $result);
        $this->assertEquals('{"status":"pass","tag":"1.0"}', $result->getBody()->getContents());
    }
}
