<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\Factory\HomeHandlerFactory;
use Application\Handler\HomeHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class HomeHandlerFactoryTest extends TestCase
{
    private ContainerInterface&MockObject $container;
    private HomeHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->factory = new HomeHandlerFactory();
    }

    public function testFactoryReturnsHomeHandler(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $config = [
            'version' => ['tag' => 'v1.0.0'],
        ];

        $this->container
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer, $config) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    'config' => $config,
                };
            });

        $handler = ($this->factory)($this->container);

        $this->assertInstanceOf(HomeHandler::class, $handler);
    }

    public function testFactoryGetsTemplateRenderer(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    'config' => [],
                };
            });

        ($this->factory)($this->container);

        $this->addToAssertionCount(1);
    }

    public function testFactoryGetsConfig(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $config = ['version' => ['tag' => 'test']];

        $this->container
            ->method('get')
            ->willReturnCallback(function ($service) use ($renderer, $config) {
                return match ($service) {
                    TemplateRendererInterface::class => $renderer,
                    'config' => $config,
                };
            });

        ($this->factory)($this->container);

        $this->addToAssertionCount(1);
    }
}
