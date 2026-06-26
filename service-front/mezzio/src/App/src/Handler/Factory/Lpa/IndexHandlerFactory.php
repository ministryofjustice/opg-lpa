<?php

declare(strict_types=1);

namespace App\Handler\Factory\Lpa;

use App\Handler\Lpa\IndexHandler;
use App\Service\Lpa\Metadata;
use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class IndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): IndexHandler
    {
        return new IndexHandler(
            $container->get(Metadata::class),
            $container->get(UrlHelper::class),
        );
    }
}
