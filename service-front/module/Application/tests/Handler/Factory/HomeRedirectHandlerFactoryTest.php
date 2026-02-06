<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\HomeRedirectHandlerFactory;
use Application\Handler\HomeRedirectHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HomeRedirectHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private HomeRedirectHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new HomeRedirectHandlerFactory();
    }

    public function testFactoryReturnsHomeRedirectHandler(): void
    {
        $config = [
            'redirects' => ['index' => 'https://example.com'],
        ];

        $this->container
            ->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn($config);

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(HomeRedirectHandler::class, $handler);
    }
}
