<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\HttpClientFactory;
use Http\Adapter\Guzzle7\Client;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HttpClientFactoryTest extends TestCase
{
    public function testFactoryReturnsGuzzleClient(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('get');

        $client = (new HttpClientFactory())($container);

        $this->assertInstanceOf(Client::class, $client);
    }
}
