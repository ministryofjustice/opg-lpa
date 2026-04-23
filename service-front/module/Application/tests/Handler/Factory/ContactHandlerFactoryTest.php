<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\ContactHandler;
use Application\Handler\Factory\ContactHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContactHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsContactHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $handler = (new ContactHandlerFactory())($container);

        $this->assertInstanceOf(ContactHandler::class, $handler);
    }
}
