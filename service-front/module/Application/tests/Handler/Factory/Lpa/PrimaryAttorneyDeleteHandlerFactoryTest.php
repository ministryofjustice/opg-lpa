<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandlerFactory;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Applicant;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrimaryAttorneyDeleteHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [Applicant::class, $this->createMock(Applicant::class)],
                [ReplacementAttorneyCleanup::class, $this->createMock(ReplacementAttorneyCleanup::class)],
            ]);

        $factory = new PrimaryAttorneyDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(PrimaryAttorneyDeleteHandler::class, $handler);
    }
}
