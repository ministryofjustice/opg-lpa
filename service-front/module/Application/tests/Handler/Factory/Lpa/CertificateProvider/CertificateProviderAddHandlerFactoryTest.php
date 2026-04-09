<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderAddHandlerFactory;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CertificateProviderAddHandlerFactoryTest extends TestCase
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
                [Metadata::class, $this->createMock(Metadata::class)],
                [ActorReuseDetailsService::class, $this->createMock(ActorReuseDetailsService::class)],
            ]);

        $factory = new CertificateProviderAddHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CertificateProviderAddHandler::class, $handler);
    }
}
