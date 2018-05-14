<?php

namespace Auth\Model\DataAccess\Mongo\Factory;

use Interop\Container\ContainerInterface;
use MongoDB\Database;
use Zend\ServiceManager\Factory\FactoryInterface;

class CollectionFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $collectionName;

    private $options = [
        'typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]
    ];

    public function __construct($collectionName)
    {
        $this->collectionName = $collectionName;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return \MongoDB\Collection
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        /** @var Database $database */
        $database = $container->get(DatabaseFactory::class);

        return $database->selectCollection($this->collectionName, $this->options);
    }
}
