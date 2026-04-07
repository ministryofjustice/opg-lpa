<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandlerFactory;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyConfirmDeleteHandlerFactoryTest extends TestCase
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

        $factory = new PrimaryAttorneyConfirmDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(PrimaryAttorneyConfirmDeleteHandler::class, $handler);
    }
}
