<?php

namespace Application\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Mongo\DatabaseFactory;
use Interop\Container\ContainerInterface;
use MongoDB\Collection;
use Zend\ServiceManager\Factory\FactoryInterface;
use Exception;

class CollectionFactory implements FactoryInterface
{
    /**
     * @var array
     */
    private $collectionConfig = [
        'default' => [
            ApiLpaCollection::class         => 'lpa',
            ApiStatsLpasCollection::class   => 'lpaStats',
            ApiUserCollection::class        => 'user',
            ApiWhoCollection::class         => 'whoAreYou',
        ],
        'auth' => [
            AuthUserCollection::class       => 'user',
            AuthLogCollection::class        => 'log',
        ],
    ];

    /**
     * Options to be used when creating the mongo collection
     * Same for all wrappers
     *
     * @var array
     */
    private $collectionOptions = [
        'typeMap' => [
            'root' => 'array',
            'document' => 'array',
            'array' => 'array'
        ]
    ];

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return mixed
     * @throws Exception
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $configKey = null;
        $collectionName = null;

        foreach ($this->collectionConfig as $thisConfigKey => $collectionSubConfig) {
            foreach ($collectionSubConfig as $thisCollectionClassName => $thisCollectionName) {
                if ($thisCollectionClassName == $requestedName) {
                    $configKey = $thisConfigKey;
                    $collectionName = $thisCollectionName;
                    break 2;
                }
            }
        }

        if (is_null($configKey) || is_null($collectionName)) {
            throw new Exception(sprintf('%s can not be created using %s', $requestedName, get_class($this)));
        }

        $database = $container->get(DatabaseFactory::class . '-' . $configKey);

        /** @var Collection $mongoCollection */
        $mongoCollection = $database->selectCollection($collectionName, $this->collectionOptions);

        return new $requestedName($mongoCollection);
    }
}
