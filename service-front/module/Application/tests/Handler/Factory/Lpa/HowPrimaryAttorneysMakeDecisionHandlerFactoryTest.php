<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\HowPrimaryAttorneysMakeDecisionHandlerFactory;
use Application\Handler\Lpa\HowPrimaryAttorneysMakeDecisionHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HowPrimaryAttorneysMakeDecisionHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(6))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [FormElementManager::class, $this->createMock(FormElementManager::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                ['ApplicantService', $this->createMock(ApplicantService::class)],
                [ReplacementAttorneyCleanup::class, $this->createMock(ReplacementAttorneyCleanup::class)],
            ]);

        $factory = new HowPrimaryAttorneysMakeDecisionHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(HowPrimaryAttorneysMakeDecisionHandler::class, $handler);
    }
}
