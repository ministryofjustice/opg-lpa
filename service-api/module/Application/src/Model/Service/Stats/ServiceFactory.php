<?php

namespace Application\Model\Service\Stats;

use Application\Model\DataAccess\Mongo\CollectionFactory;
use Auth\Model\Service\StatsService as AuthStatsService;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ServiceFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Service
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $collection = $container->get(CollectionFactory::class . '-api-stats-lpas');
        $authStatsService = $container->get(AuthStatsService::class);

        return new Service($collection, $authStatsService);
    }
}
