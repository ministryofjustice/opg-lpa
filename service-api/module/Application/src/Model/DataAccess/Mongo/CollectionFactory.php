<?php

namespace Application\Model\DataAccess\Mongo;

use Interop\Container\ContainerInterface;
use MongoDB\Database;
use Zend\ServiceManager\Factory\FactoryInterface;

class CollectionFactory implements FactoryInterface
{
    /**
     * @var string
     */
    private $collectionName;

    /**
     * @var string
     */
    private $configKey;

    private $options = [
        'typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]
    ];

    /**
     * @param $collectionName
     * @param string $configKey
     */
    public function __construct($collectionName, $configKey = 'default')
    {
        $this->collectionName = $collectionName;
        $this->configKey = $configKey;
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
        $database = $container->get(DatabaseFactory::class . '-' . $this->configKey);

        return $database->selectCollection($this->collectionName, $this->options);
    }
}
