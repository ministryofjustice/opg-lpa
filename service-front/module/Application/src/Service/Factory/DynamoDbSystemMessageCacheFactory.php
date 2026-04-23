<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Application\Adapter\DynamoDbKeyValueStore;
use Psr\Container\ContainerInterface;

class DynamoDbSystemMessageCacheFactory
{
    public function __invoke(ContainerInterface $container): DynamoDbKeyValueStore
    {
        $config = $container->get('config')['admin']['dynamodb'];
        $config['keyPrefix'] = $container->get('config')['stack']['name'];

        $dynamoDbAdapter = new DynamoDbKeyValueStore($config);
        $dynamoDbAdapter->setDynamoDbClient($container->get('DynamoDbSystemMessageClient'));

        return $dynamoDbAdapter;
    }
}
