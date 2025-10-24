<?php

namespace Application\View\Helper;

use Application\Model\Service\Session\PersistentSessionDetails;
use Psr\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class RouteNameFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return RouteName
     */
    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null)
    {
        /** @var PersistentSessionDetails $sessionDetails */
        $sessionDetails = $container->get('PersistentSessionDetails');

        $currentRoute = $sessionDetails->getCurrentRoute();
        $previousRoute = $sessionDetails->getPreviousRoute();

        return new RouteName($currentRoute, $previousRoute);
    }
}
