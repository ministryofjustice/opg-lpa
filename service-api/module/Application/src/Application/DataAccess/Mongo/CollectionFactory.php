<?php

namespace Application\DataAccess\Mongo;

use MongoDB\Collection;
use MongoDB\Database;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

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
     * Create MongoDB Collection
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return Collection mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        /** @var Database $database */
        $database = $serviceLocator->get(DatabaseFactory::class);

        return $database->selectCollection($this->collectionName, $this->options);
    }
}
