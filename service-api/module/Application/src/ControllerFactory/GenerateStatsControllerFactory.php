<?php

namespace Application\ControllerFactory;

use Application\Controller\Console\GenerateStatsController;
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
        /** @var StatsService $statsService */
        $statsService = $container->get(StatsService::class);

        return new GenerateStatsController($statsService);
    }
}
