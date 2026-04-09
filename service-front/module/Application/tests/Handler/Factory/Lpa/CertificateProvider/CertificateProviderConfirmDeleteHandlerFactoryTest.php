<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\CertificateProvider;

use Application\Handler\Factory\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandlerFactory;
use Application\Handler\Lpa\CertificateProvider\CertificateProviderConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class CertificateProviderConfirmDeleteHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
            ]);

        $factory = new CertificateProviderConfirmDeleteHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(CertificateProviderConfirmDeleteHandler::class, $handler);
    }
}
