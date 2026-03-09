<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\ConfirmDeleteLpaHandlerFactory;
use Application\Handler\Lpa\ConfirmDeleteLpaHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ConfirmDeleteLpaHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHandlerInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap([
            [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
            [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
        ]);

        $factory = new ConfirmDeleteLpaHandlerFactory();
        $handler = $factory($container, ConfirmDeleteLpaHandler::class);

        $this->assertInstanceOf(ConfirmDeleteLpaHandler::class, $handler);
    }
}
