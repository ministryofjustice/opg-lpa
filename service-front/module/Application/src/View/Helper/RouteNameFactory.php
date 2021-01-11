<?php

namespace Application\View\Helper;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RouteNameFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RouteName
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {

        /** @var ContainerInterface $sessionDetails */
        $sessionDetails = $container->get('PersistentSessionDetails');

        return new RouteName($sessionDetails->currentRoute, $sessionDetails->previousRoute);
    }
}
