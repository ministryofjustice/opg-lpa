<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderDeleteHandlerFactory;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CertificateProviderDeleteHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
            ]);

        $factory = new CertificateProviderDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CertificateProviderDeleteHandler::class, $handler);
    }
}
