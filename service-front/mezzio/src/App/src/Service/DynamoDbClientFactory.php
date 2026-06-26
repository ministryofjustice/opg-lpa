<?php

declare(strict_types=1);

namespace App\Service;

use Aws\DynamoDb\DynamoDbClient;
use Psr\Container\ContainerInterface;

class DynamoDbClientFactory
{
    public function __invoke(ContainerInterface $container): DynamoDbClient
    {
        $config = $container->get('config');

        return new DynamoDbClient($config['admin']['dynamodb']['client'] ?? [
            'region'   => getenv('AWS_REGION') ?: 'eu-west-1',
            'version'  => '2012-08-10',
            'endpoint' => getenv('OPG_LPA_COMMON_DYNAMODB_ENDPOINT') ?: null,
        ]);
    }
}
