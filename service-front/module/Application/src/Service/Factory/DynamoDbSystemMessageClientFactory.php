<?php

declare(strict_types=1);

namespace Application\Service\Factory;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class DynamoDbSystemMessageClientFactory
{
    public function __invoke(ContainerInterface $container): DynamoDbClient
    {
        return new DynamoDbClient(
            $container->get('config')['admin']['dynamodb']['client'],
        );
    }
}
