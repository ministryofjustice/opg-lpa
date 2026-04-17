<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\Download;

use Application\Handler\Factory\Lpa\Download\DownloadCheckHandlerFactory;
use Application\Handler\Lpa\Download\DownloadCheckHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DownloadCheckHandlerFactoryTest extends TestCase
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

        $factory = new DownloadCheckHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(DownloadCheckHandler::class, $handler);
    }
}
