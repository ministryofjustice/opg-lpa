<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\PrivacyHandlerFactory;
use Application\Handler\PrivacyHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class PrivacyHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsPrivacyHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $handler = (new PrivacyHandlerFactory())($container);

        $this->assertInstanceOf(PrivacyHandler::class, $handler);
    }
}
