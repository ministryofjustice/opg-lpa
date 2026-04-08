<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\CompleteIndexHandlerFactory;
use Application\Handler\Lpa\CompleteIndexHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Service\CompleteViewParamsHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CompleteIndexHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [CompleteViewParamsHelper::class, $this->createMock(CompleteViewParamsHelper::class)],
            ]);

        $factory = new CompleteIndexHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CompleteIndexHandler::class, $handler);
    }
}
