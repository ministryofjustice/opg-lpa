<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Service\Factory\AppFiltersExtensionFactory;
use Application\View\Twig\AppFiltersExtension;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class AppFiltersExtensionFactoryTest extends TestCase
{
    public function testFactoryReturnsAppFiltersExtension(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())
            ->method('get')
            ->with('config')
            ->willReturn(['version' => ['tag' => 'v1']]);

        $extension = (new AppFiltersExtensionFactory())($container);

        $this->assertInstanceOf(AppFiltersExtension::class, $extension);
    }
}
