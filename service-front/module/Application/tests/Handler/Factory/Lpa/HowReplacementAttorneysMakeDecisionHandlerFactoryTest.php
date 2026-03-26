<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\HowReplacementAttorneysMakeDecisionHandlerFactory;
use Application\Handler\Lpa\HowReplacementAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HowReplacementAttorneysMakeDecisionHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
            ]);

        $factory = new HowReplacementAttorneysMakeDecisionHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(HowReplacementAttorneysMakeDecisionHandler::class, $handler);
    }
}
