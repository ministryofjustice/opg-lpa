<?php

namespace Application\Controller\Console;

use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\Sdk;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Console\Controller\AbstractConsoleController;

class DynamoDbController extends AbstractConsoleController
{
    use LoggerTrait;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function initAction()
    {
        $sessionDynamoDb = $this->config['session']['dynamodb'];
        $cronLockDynamoDb = $this->config['cron']['lock']['dynamodb'];
        $adminDynamoDb = $this->config['admin']['dynamodb'];

        $this->createTable($sessionDynamoDb);
        $this->createTable($cronLockDynamoDb);
        $this->createTable($adminDynamoDb);

        $this->updateTimeToLive($sessionDynamoDb);
    }

    /**
     * @param $dynamoDbConfig
     */
    private function createTable(array $dynamoDbConfig): void
    {
        if ($dynamoDbConfig['auto_create'] === false) {
            echo "DynamoDB table {$dynamoDbConfig['settings']['table_name']} not set to auto create\n";
            return;
        }

        $sdk = new Sdk([
            'endpoint' => $dynamoDbConfig['client']['endpoint'],
            'region' => $dynamoDbConfig['client']['region'],
            'version' => $dynamoDbConfig['client']['version']
        ]);

        $dynamoDb = $sdk->createDynamoDb();

        try {
            $dynamoDb->describeTable(['TableName' => $dynamoDbConfig['settings']['table_name']]);
            echo "DynamoDB table {$dynamoDbConfig['settings']['table_name']} already exists\n";
        } catch (DynamoDbException $ex) {
            if ($ex->getAwsErrorCode() == 'ResourceNotFoundException') {
                //Table doesn't exist
                echo "Creating DynamoDB table {$dynamoDbConfig['settings']['table_name']}\n";

                $params = [
                    'TableName' => $dynamoDbConfig['settings']['table_name'],
                    'KeySchema' => [
                        [
                            'AttributeName' => 'id',
                            'KeyType' => 'HASH'
                        ]
                    ],
                    'AttributeDefinitions' => [
                        [
                            'AttributeName' => 'id',
                            'AttributeType' => 'S'
                        ]
                    ],
                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 10,
                        'WriteCapacityUnits' => 10
                    ]
                ];

                try {
                    $result = $dynamoDb->createTable($params);
                    echo 'Created table.  Status: ' . $result['TableDescription']['TableStatus'] . "\n";
                } catch (DynamoDbException $e) {
                    echo "Unable to create table:\n";
                    echo $e->getMessage() . "\n";
                }
            } else {
                echo "Unable to describe table:\n";
                echo $ex->getMessage() . "\n";
            }
        }
    }

    /**
     * @param $dynamoDbConfig
     */
    private function updateTimeToLive(array $dynamoDbConfig): void
    {
        if ($dynamoDbConfig['auto_create'] === false) {
            echo "DynamoDB table {$dynamoDbConfig['settings']['table_name']} not set to auto create\n";
            return;
        }

        $sdk = new Sdk([
            'endpoint' => $dynamoDbConfig['client']['endpoint'],
            'region' => $dynamoDbConfig['client']['region'],
            'version' => $dynamoDbConfig['client']['version']
        ]);

        $dynamoDb = $sdk->createDynamoDb();

        try {
            $dynamoDb->updateTimeToLive(['TableName' => $dynamoDbConfig['settings']['table_name'],
                'TimeToLiveSpecification' => ["Enabled" => $dynamoDbConfig['settings']['ttl_enabled'],
                    "AttributeName" => "expires"]]);
        } catch (DynamoDbException $ex) {
            echo 'Unable to update time to live on table ' . $dynamoDbConfig['settings']['table_name'] . ':\n';
            echo $ex->getMessage() . "\n";
        }
    }
}