<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\ReuseDetailsHandlerFactory;
use Application\Handler\Lpa\ReuseDetailsHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ReuseDetailsHandlerFactoryTest extends TestCase
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
                [ActorReuseDetailsService::class, $this->createMock(ActorReuseDetailsService::class)],
            ]);

        $factory = new ReuseDetailsHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(ReuseDetailsHandler::class, $handler);
    }
}
