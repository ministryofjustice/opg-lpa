<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\TwigViewRendererFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Twig\Environment;

class TwigViewRendererFactoryTest extends TestCase
{
    public function testFactoryReturnsTwigEnvironment(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(['twig' => ['cache_dir' => false]]);

        $renderer = (new TwigViewRendererFactory())($container);

        $this->assertInstanceOf(Environment::class, $renderer);
    }
}
