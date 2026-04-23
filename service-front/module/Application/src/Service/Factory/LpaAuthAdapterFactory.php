<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Model\Service\Authentication\Adapter\LpaAuthAdapter;
use Psr\Container\ContainerInterface;

class LpaAuthAdapterFactory
{
    public function __invoke(ContainerInterface $container): LpaAuthAdapter
    {
        return new LpaAuthAdapter($container->get('ApiClient'));
    }
}
