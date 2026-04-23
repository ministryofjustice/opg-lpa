<?php

declare(strict_types=1);

namespace ApplicationTest\Middleware\Factory;

use Application\Middleware\Factory\RequestLoggingMiddlewareFactory;
use Application\Middleware\RequestLoggingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class RequestLoggingMiddlewareFactoryTest extends TestCase
{
    public function testFactoryReturnsRequestLoggingMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(LoggerInterface::class)
            ->willReturn($this->createMock(LoggerInterface::class));

        $middleware = (new RequestLoggingMiddlewareFactory())($container);

        $this->assertInstanceOf(RequestLoggingMiddleware::class, $middleware);
    }
}
