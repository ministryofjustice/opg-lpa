<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\RepeatApplicationHandlerFactory;
use Application\Handler\Lpa\RepeatApplicationHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class RepeatApplicationHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(5))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                ['Metadata', $this->createMock(Metadata::class)],
            ]);

        $factory = new RepeatApplicationHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(RepeatApplicationHandler::class, $handler);
    }
}
