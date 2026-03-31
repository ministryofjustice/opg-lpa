<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\ReplacementAttorneyConfirmDeleteHandlerFactory;
use Application\Handler\Lpa\ReplacementAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyConfirmDeleteHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
            ]);

        $factory = new ReplacementAttorneyConfirmDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(ReplacementAttorneyConfirmDeleteHandler::class, $handler);
    }
}
