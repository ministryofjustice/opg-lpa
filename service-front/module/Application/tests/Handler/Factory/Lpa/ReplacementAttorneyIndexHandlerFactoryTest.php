<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\ReplacementAttorneyIndexHandlerFactory;
use Application\Handler\Lpa\ReplacementAttorneyIndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReplacementAttorneyIndexHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [Metadata::class, $this->createMock(Metadata::class)],
            ]);

        $factory = new ReplacementAttorneyIndexHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(ReplacementAttorneyIndexHandler::class, $handler);
    }
}
