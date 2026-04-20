<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\Download;

use Application\Handler\Factory\Lpa\Download\DownloadHandlerFactory;
use Application\Handler\Lpa\Download\DownloadHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DownloadHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(4))
            ->method('get')
            ->willReturnMap([
                [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [LoggerInterface::class, $this->createMock(LoggerInterface::class)],
            ]);

        $factory = new DownloadHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(DownloadHandler::class, $handler);
    }
}
