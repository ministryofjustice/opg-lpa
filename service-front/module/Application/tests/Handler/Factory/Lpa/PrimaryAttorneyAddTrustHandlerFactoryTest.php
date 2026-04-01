<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandlerFactory;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyAddTrustHandlerFactoryTest extends TestCase
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
                [SessionUtility::class, $this->createMock(SessionUtility::class)],
            ]);

        $factory = new PrimaryAttorneyAddTrustHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(PrimaryAttorneyAddTrustHandler::class, $handler);
    }
}
