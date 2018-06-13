<?php

namespace Application\Model\Service\System;

use Application\Model\DataAccess\Mongo\CollectionFactory;
use Interop\Container\ContainerInterface;
use MongoDB\Collection;
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
        /** @var Collection $lpaCollection */
        $lpaCollection = $container->get(CollectionFactory::class . '-api-lpa');
        /** @var Collection $statsLpaCollection */
        $statsLpaCollection = $container->get(CollectionFactory::class . '-api-stats-lpas');
        /** @var Collection $statsWhoCollection */
        $statsWhoCollection = $container->get(CollectionFactory::class . '-api-stats-who');

        return new Stats($lpaCollection, $statsLpaCollection, $statsWhoCollection);
    }
}
