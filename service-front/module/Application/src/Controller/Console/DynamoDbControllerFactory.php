<?php

namespace Application\Controller\Console;

use Aws\Sdk;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class DynamoDbControllerFactory implements FactoryInterface
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
        $config = $container->get('config');

        /*$config['keyPrefix'] = $sm->get('config')['stack']['name'];

        return new DynamoCronLock($config);

        $sdk = new Sdk([
            'endpoint'   => 'http://localhost:8000',
            'region'   => 'us-west-2',
            'version'  => 'latest'
        ]);*/

        /*source ../lpa-local-dev/develop/env/base.env

    export AWS_DEFAULT_REGION=eu-west-1
    export AWS_ACCESS_KEY_ID AWS_SECRET_ACCESS_KEY

        aws dynamodb create-table \
      --endpoint-url http://localhost:8000 \
      --attribute-definitions AttributeName=id,AttributeType=S \
      --table-name ${OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE} \
      --key-schema AttributeName=id,KeyType=HASH \
      --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10
# not supported on local dynamodb as at June 2017
# needs to be reviewed in the future
#    aws dynamodb update-time-to-live \
#      --endpoint-url http://localhost:8000 \
#      --table-name ${OPG_LPA_COMMON_SESSION_DYNAMODB_TABLE} \
#      --time-to-live-specification '{"Enabled": true, "AttributeName": "expires"}'


    aws dynamodb create-table \
      --endpoint-url http://localhost:8000 \
      --attribute-definitions AttributeName=id,AttributeType=S \
      --table-name ${OPG_LPA_COMMON_CRONLOCK_DYNAMODB_TABLE} \
      --key-schema AttributeName=id,KeyType=HASH \
      --provisioned-throughput ReadCapacityUnits=10,WriteCapacityUnits=10

      ../opg-lpa-pdf/vendor/bin/dynamo-queue create --endpoint http://localhost:8000 --region eu-west-1 --table ${OPG_LPA_COMMON_QUEUE_DYNAMODB_TABLE}*/

        return new DynamoDbController($config);
    }
}
