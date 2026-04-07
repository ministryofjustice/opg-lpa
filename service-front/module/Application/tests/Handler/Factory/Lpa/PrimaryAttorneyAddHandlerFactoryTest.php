<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandlerFactory;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyAddHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(7))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [Applicant::class, $this->createMock(Applicant::class)],
                [ReplacementAttorneyCleanup::class, $this->createMock(ReplacementAttorneyCleanup::class)],
                [ActorReuseDetailsService::class, $this->createMock(ActorReuseDetailsService::class)],
            ]);

        $factory = new PrimaryAttorneyAddHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(PrimaryAttorneyAddHandler::class, $handler);
    }
}
