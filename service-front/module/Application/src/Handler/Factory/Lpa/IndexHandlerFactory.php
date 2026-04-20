<?php

declare(strict_types=1);

namespace Application\Handler\Factory\Lpa;

use Application\Handler\Lpa\IndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Session\SessionManagerSupport;
use Psr\Container\ContainerInterface;

class IndexHandlerFactory
{
    public function __invoke(ContainerInterface $container): IndexHandler
    {
        return new IndexHandler(
            $container->get(Metadata::class),
            $container->get(MvcUrlHelper::class),
            $container->get(SessionManagerSupport::class),
        );
    }
}
