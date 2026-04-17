<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\IndexHandlerFactory;
use Application\Handler\Lpa\IndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Session\SessionManagerSupport;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class IndexHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->exactly(3))
            ->method('get')
            ->willReturnMap([
                [Metadata::class, $this->createMock(Metadata::class)],
                [MvcUrlHelper::class, $this->createMock(MvcUrlHelper::class)],
                [SessionManagerSupport::class, $this->createMock(SessionManagerSupport::class)],
            ]);

        $factory = new IndexHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(IndexHandler::class, $handler);
    }
}
