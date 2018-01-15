<?php

namespace Application\DataAccess;

use Application\DataAccess\Mongo\CollectionFactory;
use MongoDB\Collection;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class UserDalFactory
 * @package Application\DataAccess
 */
class UserDalFactory implements FactoryInterface
{
    /**
     * Create Stats service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return UserDal
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var Collection $collection */
        $collection = $serviceLocator->get(CollectionFactory::class . '-user');

        return new UserDal($collection);
    }
}
