<?php

declare(strict_types=1);

namespace Handler;

use Laminas\Diactoros\ServerRequest;
use MakeShared\Handler\PingHandlerElb;
use PHPUnit\Framework\TestCase;

class PingHandlerElbTest extends TestCase
{
    public function testHandlerReturns200(): void
    {
        $handler = new PingHandlerElb();

        self::assertEquals('Happy face', $handler->handle(new ServerRequest())->getBody()->getContents());
        self::assertEquals(200, $handler->handle(new ServerRequest())->getStatusCode());
    }
}
