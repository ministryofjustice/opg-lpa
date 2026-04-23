<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Application\Service\Factory\LpaAuthAdapterFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class LpaAuthAdapterFactoryTest extends TestCase
{
    public function testFactoryReturnsLpaAuthAdapter(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('ApiClient')
            ->willReturn($this->createMock(ApiClient::class));

        $adapter = (new LpaAuthAdapterFactory())($container);

        $this->assertInstanceOf(LpaAuthAdapter::class, $adapter);
    }
}
