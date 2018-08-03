<?php

namespace Application\Model\Service\System;

use Application\Model\DataAccess\Mongo\Collection\ApiLpaCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiStatsLpasCollection;
use Application\Model\DataAccess\Mongo\Collection\ApiWhoCollection;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class StatsFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return Stats
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var ApiLpaCollection $apiLpaCollection */
        $apiLpaCollection = $container->get(ApiLpaCollection::class);
        /** @var ApiStatsLpasCollection $apiStatsLpasCollection */
        $apiStatsLpasCollection = $container->get(ApiStatsLpasCollection::class);
        /** @var ApiWhoCollection $apiWhoCollection */
        $apiWhoCollection = $container->get(ApiWhoCollection::class);

        return new Stats($apiLpaCollection, $apiStatsLpasCollection, $apiWhoCollection);
    }
}
