<?php

namespace Application\Model\Service\System;

use Application\DataAccess\Mongo\CollectionFactory;
use MongoDB\Collection;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class StatsFactory implements FactoryInterface
{
    /**
     * Create Stats service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Stats
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var Collection $lpaCollection */
        $lpaCollection = $serviceLocator->get(CollectionFactory::class . '-lpa');
        /** @var Collection $statsLpaCollection */
        $statsLpaCollection = $serviceLocator->get(CollectionFactory::class . '-stats-lpas');
        /** @var Collection $statsWhoCollection */
        $statsWhoCollection = $serviceLocator->get(CollectionFactory::class . '-stats-who');

        return new Stats($lpaCollection, $statsLpaCollection, $statsWhoCollection);
    }
}