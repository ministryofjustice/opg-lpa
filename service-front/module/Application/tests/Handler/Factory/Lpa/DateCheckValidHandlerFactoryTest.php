<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory\Lpa;

use Application\Handler\Factory\Lpa\DateCheckValidHandlerFactory;
use Application\Handler\Lpa\DateCheckValidHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DateCheckValidHandlerFactoryTest extends TestCase
{
    public function testFactoryCreatesHandler(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($this->createMock(TemplateRendererInterface::class));

        $factory = new DateCheckValidHandlerFactory();
        $handler = $factory($container);

        $this->assertInstanceOf(DateCheckValidHandler::class, $handler);
    }
}
