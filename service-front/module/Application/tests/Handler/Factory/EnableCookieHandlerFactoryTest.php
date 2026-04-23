<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\EnableCookieHandler;
use Application\Handler\Factory\EnableCookieHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class EnableCookieHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsEnableCookieHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $handler = (new EnableCookieHandlerFactory())($container);

        $this->assertInstanceOf(EnableCookieHandler::class, $handler);
    }
}
