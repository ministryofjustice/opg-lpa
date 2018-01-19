<?php

namespace Application\DataAccess\Mongo;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use MongoDB\Database;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class CollectionFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $collectionName;

    private $options = ['typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array']];

    public function __construct($collectionName)
    {
        $this->collectionName = $collectionName;
    }

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
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class);

        return $database->selectCollection($this->collectionName, $this->options);
    }
}
