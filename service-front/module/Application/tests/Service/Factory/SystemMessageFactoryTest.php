<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Adapter\DynamoDbKeyValueStore;
use Application\Service\Factory\SystemMessageFactory;
use Application\Service\SystemMessage;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class SystemMessageFactoryTest extends TestCase
{
    public function testInvokeReturnsSystemMessageService(): void
    {
        $cache = $this->createMock(DynamoDbKeyValueStore::class);

        $container = $this->createMock(ContainerInterface::class);
        $container
            ->expects($this->once())
            ->method('get')
            ->with('DynamoDbSystemMessageCache')
            ->willReturn($cache);

        $factory = new SystemMessageFactory();

        $service = $factory($container);

        $this->assertInstanceOf(SystemMessage::class, $service);
    }
}
