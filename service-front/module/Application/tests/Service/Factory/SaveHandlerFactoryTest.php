<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\Session\FilteringSaveHandler;
use Application\Model\Service\Session\WritePolicy;
use Application\Model\Service\Redis\RedisClient;
use Application\Service\Factory\SaveHandlerFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class SaveHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsSaveHandlerWithWritePolicy(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(WritePolicy::class)->willReturn(true);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            RedisClient::class  => $this->createMock(RedisClient::class),
            // WritePolicy is final — use a real instance (no constructor args needed in Mezzio).
            WritePolicy::class  => new WritePolicy(),
        });

        $handler = (new SaveHandlerFactory())($container);

        $this->assertInstanceOf(FilteringSaveHandler::class, $handler);
    }

    public function testFactoryReturnsSaveHandlerWithoutWritePolicy(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->with(WritePolicy::class)->willReturn(false);
        $container->method('get')
            ->with(RedisClient::class)
            ->willReturn($this->createMock(RedisClient::class));

        $handler = (new SaveHandlerFactory())($container);

        $this->assertInstanceOf(FilteringSaveHandler::class, $handler);
    }
}
