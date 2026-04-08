<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\ReplacementAttorneyDeleteHandlerFactory;
use Application\Handler\Lpa\ReplacementAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyDeleteHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [ReplacementAttorneyCleanup::class, $this->createMock(ReplacementAttorneyCleanup::class)],
            ]);

        $factory = new ReplacementAttorneyDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(ReplacementAttorneyDeleteHandler::class, $handler);
    }
}
