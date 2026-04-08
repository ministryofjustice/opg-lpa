<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\CompleteViewDocsHandlerFactory;
use Application\Handler\Lpa\CompleteViewDocsHandler;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Service\CompleteViewParamsHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CompleteViewDocsHandlerFactoryTest extends TestCase
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

        $factory = new CompleteViewDocsHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CompleteViewDocsHandler::class, $handler);
    }
}
