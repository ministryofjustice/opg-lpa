<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderEditHandlerFactory;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Form\FormElementManager;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CertificateProviderEditHandlerFactoryTest extends TestCase
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

        $factory = new CertificateProviderEditHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CertificateProviderEditHandler::class, $handler);
    }
}
