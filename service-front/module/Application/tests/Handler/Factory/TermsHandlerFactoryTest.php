<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Factory;

use Application\Handler\Factory\TermsHandlerFactory;
use Application\Handler\TermsHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class TermsHandlerFactoryTest extends TestCase
{
    public function testFactoryReturnsTermsHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with(TemplateRendererInterface::class)
            ->willReturn($renderer);

        $handler = (new TermsHandlerFactory())($container);

        $this->assertInstanceOf(TermsHandler::class, $handler);
    }
}
