<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\CreateLpaHandlerFactory;
use Application\Handler\Lpa\CreateLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Session\SessionManagerSupport;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CreateLpaHandlerFactoryTest extends TestCase
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
            [SessionManagerSupport::class, $this->createMock(SessionManagerSupport::class)],
        ]);

        $factory = new CreateLpaHandlerFactory();
        $handler = $factory($container, CreateLpaHandler::class);

        $this->assertInstanceOf(CreateLpaHandler::class, $handler);
    }
}
