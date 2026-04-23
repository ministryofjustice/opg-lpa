<?php

declare(strict_types=1);

namespace ApplicationTest\Service\Factory;

use Application\Model\Service\Session\NativeSessionConfig;
use Application\Service\Factory\NativeSessionConfigFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NativeSessionConfigFactoryTest extends TestCase
{
    public function testFactoryReturnsNativeSessionConfig(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'       => ['session' => ['native_settings' => ['name' => 'lpa']]],
            'SaveHandler'  => $this->createMock(\Laminas\Session\SaveHandler\SaveHandlerInterface::class),
        });

        $config = (new NativeSessionConfigFactory())($container);

        $this->assertInstanceOf(NativeSessionConfig::class, $config);
    }

    public function testFactoryUsesEmptySettingsWhenMissing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')->willReturnCallback(fn($s) => match ($s) {
            'config'       => [],
            'SaveHandler'  => $this->createMock(\Laminas\Session\SaveHandler\SaveHandlerInterface::class),
        });

        $config = (new NativeSessionConfigFactory())($container);

        $this->assertInstanceOf(NativeSessionConfig::class, $config);
    }
}
