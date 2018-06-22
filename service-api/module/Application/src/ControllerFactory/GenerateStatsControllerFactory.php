<?php

namespace Application\ControllerFactory;

use Application\Controller\Console\GenerateStatsController;
use Application\Model\Service\System\DynamoCronLock;
use Application\Model\Service\System\Stats as StatsService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class GenerateStatsControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return GenerateStatsController
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var DynamoCronLock $cronLock */
        $cronLock = $container->get('DynamoCronLock');
        /** @var StatsService $statsService */
        $statsService = $container->get(StatsService::class);

        return new GenerateStatsController($cronLock, $statsService);
    }
}
