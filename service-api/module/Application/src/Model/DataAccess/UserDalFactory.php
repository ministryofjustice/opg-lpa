<?php

namespace Application\Model\DataAccess;

use Application\Model\DataAccess\Mongo\CollectionFactory;
use Interop\Container\ContainerInterface;
use MongoDB\Collection;
use Zend\ServiceManager\Factory\FactoryInterface;

class UserDalFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return UserDal
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Collection $collection */
        $collection = $container->get(CollectionFactory::class . '-user');

        return new UserDal($collection);
    }
}
