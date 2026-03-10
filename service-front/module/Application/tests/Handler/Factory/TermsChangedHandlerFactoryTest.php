<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\TermsChangedHandlerFactory;
use Application\Handler\TermsChangedHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TermsChangedHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsHandlerInstance(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $container->method('get')->willReturnMap([
            [TemplateRendererInterface::class, $this->createMock(TemplateRendererInterface::class)],
        ]);

        $factory = new TermsChangedHandlerFactory();
        $handler = $factory($container, TermsChangedHandler::class);

        $this->assertInstanceOf(TermsChangedHandler::class, $handler);
    }
}
