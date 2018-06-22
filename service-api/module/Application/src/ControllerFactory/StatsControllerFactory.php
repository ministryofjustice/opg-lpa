<?php

namespace Application\ControllerFactory;

use Application\Controller\StatsController;
use Application\Model\Service\Stats\Service as StatsService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class StatsControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return StatsController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var StatsService $statsService */
        $statsService = $container->get(StatsService::class);

        return new StatsController($statsService);
    }
}
