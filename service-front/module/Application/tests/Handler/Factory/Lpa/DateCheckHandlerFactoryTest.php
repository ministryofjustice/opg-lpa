<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\DateCheckHandlerFactory;
use Application\Handler\Lpa\DateCheckHandler;
use Application\Helper\MvcUrlHelper;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DateCheckHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
            ]);

        $factory = new DateCheckHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(DateCheckHandler::class, $handler);
    }
}
