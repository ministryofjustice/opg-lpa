<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\DashboardHandler;
use Application\Handler\Factory\DashboardHandlerFactory;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DashboardHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHandlerInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap([
            [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
            [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
        ]);

        $factory = new DashboardHandlerFactory();
        $handler = $factory($container, DashboardHandler::class);

        $this->assertInstanceOf(DashboardHandler::class, $handler);
    }
}
