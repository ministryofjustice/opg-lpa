<?php

namespace Application\DataAccess;

use Application\DataAccess\Mongo\CollectionFactory;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use MongoDB\Collection;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

/**
 * Class UserDalFactory
 * @package Application\DataAccess
 */
class UserDalFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Collection $collection */
        $collection = $container->get(CollectionFactory::class . '-user');

        return new UserDal($collection);
    }
}
