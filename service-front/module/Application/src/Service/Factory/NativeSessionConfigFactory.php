<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Session\NativeSessionConfig;
use Psr\Container\ContainerInterface;

class NativeSessionConfigFactory
{
    public function __invoke(ContainerInterface $container): NativeSessionConfig
    {
        $settings = $container->get('config')['session']['native_settings'] ?? [];

        return new NativeSessionConfig(
            $settings,
            $container->get('SaveHandler'),
        );
    }
}
