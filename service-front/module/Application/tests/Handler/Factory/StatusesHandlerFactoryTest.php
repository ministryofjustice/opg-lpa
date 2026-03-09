<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\StatusesHandlerFactory;
use Application\Handler\StatusesHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StatusesHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHandlerInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap([
            [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
        ]);

        $factory = new StatusesHandlerFactory();
        $handler = $factory($container, StatusesHandler::class);

        $this->assertInstanceOf(StatusesHandler::class, $handler);
    }
}
