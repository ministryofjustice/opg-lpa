<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\AccessibilityHandler;
use Application\Handler\Factory\AccessibilityHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AccessibilityHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsAccessibilityHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $handler = (new AccessibilityHandlerFactory())($container);

        $this->assertInstanceOf(AccessibilityHandler::class, $handler);
    }
}
