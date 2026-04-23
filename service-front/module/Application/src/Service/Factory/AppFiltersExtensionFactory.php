<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\View\Twig\AppFiltersExtension;
use Psr\Container\ContainerInterface;

class AppFiltersExtensionFactory
{
    public function __invoke(ContainerInterface $container): AppFiltersExtension
    {
        return new AppFiltersExtension($container->get('config'));
    }
}
