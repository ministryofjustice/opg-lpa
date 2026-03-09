<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\DeleteLpaHandlerFactory;
use Application\Handler\Lpa\DeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DeleteLpaHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHandlerInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $controllerPluginManager = $this->createMock(ContainerInterface::class);

        $controllerPluginManager->method('get')
            ->with(FlashMessenger::class)
            ->willReturn($this->createMock(FlashMessenger::class));

        $container->method('get')->willReturnMap([
            [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
            ['ControllerPluginManager', $controllerPluginManager],
        ]);

        $factory = new DeleteLpaHandlerFactory();
        $handler = $factory($container, DeleteLpaHandler::class);

        $this->assertInstanceOf(DeleteLpaHandler::class, $handler);
    }
}
