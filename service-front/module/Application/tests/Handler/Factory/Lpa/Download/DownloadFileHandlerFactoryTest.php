<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa\Download;

use Application\Handler\Factory\Lpa\Download\DownloadFileHandlerFactory;
use Application\Handler\Lpa\Download\DownloadFileHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class DownloadFileHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [LpaApplicationService::class, $this->createMock(LpaApplicationService::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [LoggerInterface::class, $this->createMock(LoggerInterface::class)],
            ]);

        $factory = new DownloadFileHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(DownloadFileHandler::class, $handler);
    }
}
