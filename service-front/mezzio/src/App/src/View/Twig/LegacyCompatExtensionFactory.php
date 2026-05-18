<?php

declare(strict_types=1);

namespace App\View\Twig;

use Psr\Container\ContainerInterface;

class LegacyCompatExtensionFactory
{
    public function __invoke(ContainerInterface $container): LegacyCompatExtension
    {
        return new LegacyCompatExtension($container->get('config'));
    }
}
