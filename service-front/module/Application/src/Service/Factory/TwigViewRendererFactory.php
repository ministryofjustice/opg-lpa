<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Psr\Container\ContainerInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigViewRendererFactory
{
    public function __invoke(ContainerInterface $container): Environment
    {
        $loader = new FilesystemLoader('module/Application/view/application');

        return new Environment(
            $loader,
            ['cache' => $container->get('config')['twig']['cache_dir']],
        );
    }
}
